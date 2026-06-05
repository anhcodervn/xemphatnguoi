<?php

namespace App\Features\Auth\Controllers;

use App\Exceptions\ApiException;
use App\Features\Auth\Requests\ForgotPasswordRequest;
use App\Features\Auth\Requests\LoginRequest;
use App\Features\Auth\Requests\RegisterRequest;
use App\Features\Auth\Services\GoogleAuthService;
use App\Features\Client\Package\Services\PackageService;
use App\Features\Client\Profile\Actions\RecordUserLogAction;
use App\Features\Client\Wallet\Services\WalletService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MailQueue;
use App\Utils\SendMessage;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly PackageService $packageService,
        private readonly RecordUserLogAction $recordUserLogAction,
        private readonly MailQueue $mailQueue,
        private readonly GoogleAuthService $googleAuthService,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'feature' => 'Auth',
            'message' => 'Auth feature index',
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        if (! $user instanceof User) {
            throw new ApiException('Không thể xác thực tài khoản.', 500);
        }

        $this->touchLastLogin($user, $request);
        $this->recordUserLogAction->handle($user, 'login', 'Đăng nhập hệ thống', $request);

        return response()->json([
            'status' => true,
            'message' => 'Đăng nhập thành công.',
            'redirect' => url('/'),
            'user' => $this->userPayload($user),
        ]);
    }

    public function apiLogin(LoginRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        $user = User::query()
            ->where($request->loginField(), $request->normalizedLogin())
            ->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            $request->hitRateLimit();

            throw new ApiException('Thông tin đăng nhập không chính xác.', 422, [
                'errors' => [
                    'login' => ['Thông tin đăng nhập không chính xác.'],
                ],
            ]);
        }

        $request->clearRateLimit();
        $this->touchLastLogin($user, $request);
        $this->recordUserLogAction->handle($user, 'api_login', 'Đăng nhập API', $request);

        $token = $user->createToken($request->userAgent() ?: 'auth-api')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Đăng nhập thành công.',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'] ?? $validated['full_name'] ?? null,
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
        ]);

        $this->walletService->createWallet($user);
        $this->recordUserLogAction->handle($user, 'register', 'Đăng ký tài khoản', $request);

        if (is_string($user->email) && $user->email !== '') {
            $this->mailQueue->dispatch(
                to: $user->email,
                subjectText: 'Đăng ký tài khoản thành công',
                title: 'Chào mừng bạn đến với hệ thống',
                messageLines: [
                    'Tài khoản của bạn đã được tạo thành công.',
                    'Bạn có thể đăng nhập và bắt đầu sử dụng các tính năng ngay bây giờ.',
                ],
                ctaText: 'Đăng nhập',
                ctaUrl: route('auth.login'),
            );
        }

        event(new Registered($user));
        SendMessage::sendInfoReport('Người dùng đăng ký mới', [
            'Loại đăng ký' => 'form',
            'User ID' => $user->id,
            'Username' => $user->username,
            'Họ tên' => $user->name,
            'Email' => $user->email,
            'Số điện thoại' => $user->phone,
            'Trạng thái' => $user->status,
            'Vai trò' => $user->role,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đăng ký thành công.',
            'redirect' => route('auth.login'),
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::broker()->sendResetLink($request->only('email'));

        return response()->json([
            'status' => true,
            'message' => 'Nếu email tồn tại trong hệ thống, liên kết đặt lại mật khẩu đã được gửi.',
        ]);
    }

    public function redirectToGoogle(Request $request): RedirectResponse
    {
        if (! $this->googleAuthService->isConfigured()) {
            return redirect()
                ->route('auth.login')
                ->with('auth_google_error', 'Đăng nhập Google chưa được cấu hình.');
        }

        $state = $this->googleAuthService->generateState();
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away($this->googleAuthService->authorizationUrl($state));
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        $expectedState = $request->session()->pull('google_oauth_state');
        $receivedState = $request->string('state')->toString();

        if (
            ! is_string($expectedState)
            || $expectedState === ''
            || $receivedState === ''
            || ! hash_equals($expectedState, $receivedState)
        ) {
            return redirect()
                ->route('auth.login')
                ->with('auth_google_error', 'Phiên đăng nhập Google không hợp lệ. Vui lòng thử lại.');
        }

        if ($request->filled('error')) {
            return redirect()
                ->route('auth.login')
                ->with('auth_google_error', 'Bạn đã hủy hoặc từ chối đăng nhập bằng Google.');
        }

        $code = $request->string('code')->toString();

        if ($code === '') {
            return redirect()
                ->route('auth.login')
                ->with('auth_google_error', 'Google không trả về mã xác thực hợp lệ.');
        }

        try {
            $googleUser = $this->googleAuthService->fetchUser($code);
            $isNewUser = false;

            $user = User::query()
                ->where('google_id', $googleUser['id'])
                ->orWhere('email', $googleUser['email'])
                ->first();

            if ($user instanceof User) {
                if ($user->status !== 'active') {
                    return redirect()
                        ->route('auth.login')
                        ->with('auth_google_error', 'Tài khoản của bạn hiện không thể đăng nhập.');
                }

                $user->forceFill([
                    'google_id' => $googleUser['id'],
                    'email' => $googleUser['email'],
                    'full_name' => $googleUser['name'],
                    'avatar' => $googleUser['avatar'],
                    'email_verified_at' => $googleUser['email_verified'] ? ($user->email_verified_at ?: now()) : $user->email_verified_at,
                ])->save();
            } else {
                $isNewUser = true;
                $user = User::query()->create([
                    'username' => Str::of($googleUser['email'])->before('@')->slug('')->value() ?: null,
                    'email' => $googleUser['email'],
                    'full_name' => $googleUser['name'],
                    'avatar' => $googleUser['avatar'],
                    'google_id' => $googleUser['id'],
                    'email_verified_at' => $googleUser['email_verified'] ? now() : null,
                    'password' => Str::random(64),
                    'status' => 'active',
                    'role' => 'user',
                ]);
            }

            Auth::guard('web')->login($user, true);
            $request->session()->regenerate();

            $this->touchLastLogin($user, $request);
            $this->recordUserLogAction->handle($user, 'google_login', 'Đăng nhập bằng Google', $request);

            if ($isNewUser) {
                SendMessage::sendInfoReport('Người dùng đăng ký mới', [
                    'Loại đăng ký' => 'google',
                    'User ID' => $user->id,
                    'Username' => $user->username,
                    'Họ tên' => $user->name,
                    'Email' => $user->email,
                    'Trạng thái' => $user->status,
                    'Vai trò' => $user->role,
                ]);
            }

            return redirect('/');
        } catch (Throwable $exception) {
            return redirect()
                ->route('auth.login')
                ->with('auth_google_error', $exception->getMessage() ?: 'Không thể đăng nhập bằng Google.');
        }
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User && $user->exists) {
            $this->recordUserLogAction->handle($user, 'logout', 'Đăng xuất hệ thống', $request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Đăng xuất thành công.',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login');
    }

    /**
     * @return array{
     *     id:int,
     *     username:string,
     *     email:?string,
     *     phone:?string,
     *     full_name:?string,
     *     role:mixed,
     *     status:mixed,
     *     wallet:array{id:int,user_id:int,type:string,balance:string,hold_balance:string,total_recharge:string,total_spent:string,created_at:?string,updated_at:?string},
     *     user_subscriptions:?array<string, mixed>
     * }
     */
    protected function userPayload(User $user): array
    {
        return [
            ...$user->only([
                'id',
                'username',
                'email',
                'phone',
                'full_name',
                'role',
                'status',
            ]),
            'wallet' => $this->walletService->getWalletInfo($user),
            'user_subscriptions' => $this->packageService->getCurrentUserSubscriptionInfo($user),
        ];
    }

    protected function touchLastLogin(User $user, Request $request): void
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();
    }
}
