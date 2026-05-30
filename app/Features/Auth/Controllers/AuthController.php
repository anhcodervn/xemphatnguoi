<?php

namespace App\Features\Auth\Controllers;

use App\Exceptions\ApiException;
use App\Features\Auth\Requests\ForgotPasswordRequest;
use App\Features\Auth\Requests\LoginRequest;
use App\Features\Auth\Requests\RegisterRequest;
use App\Features\Client\Package\Services\PackageService;
use App\Features\Client\Profile\Actions\RecordUserLogAction;
use App\Features\Client\Wallet\Services\WalletService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\MailQueue;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly PackageService $packageService,
        private readonly RecordUserLogAction $recordUserLogAction,
        private readonly MailQueue $mailQueue,
    ) {
    }

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
