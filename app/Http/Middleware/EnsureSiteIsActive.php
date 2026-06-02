<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\EditorContentRenderer;
use App\Support\SettingStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiteIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        /** @var SettingStore $settingStore */
        $settingStore = app(SettingStore::class);
        /** @var EditorContentRenderer $contentRenderer */
        $contentRenderer = app(EditorContentRenderer::class);

        if ($user instanceof User && $user->role === 'admin') {
            return $next($request);
        }

        $settings = $settingStore->getMany([
            'site_active' => true,
            'site_name' => config('app.name', 'Nạp Tiền Tự Động'),
            'site_description' => '',
            'support_email' => '',
            'hotline' => '',
            'address' => '',
            'light_logo' => '',
            'dark_logo' => '',
            'favicon' => '',
            'system_status_title' => 'Bảo trì hệ thống',
            'system_status_excerpt' => 'Hệ thống đang tạm thời gián đoạn để nâng cấp dịch vụ và tối ưu trải nghiệm. Vui lòng quay lại sau ít phút.',
            'system_status_content' => [],
            'system_updates_title' => 'Cập nhật gần đây',
            'system_updates_excerpt' => 'Đội ngũ kỹ thuật đang theo dõi và xử lý để hệ thống sớm hoạt động ổn định trở lại.',
            'system_updates_content' => [],
        ]);

        if ((bool) ($settings['site_active'] ?? true)) {
            return $next($request);
        }

        if ($request->expectsJson() || ! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Hệ thống đang bảo trì. Vui lòng thử lại sau.',
                'data' => [
                    'maintenance' => true,
                ],
            ], 503);
        }

        $statusContent = is_array($settings['system_status_content'] ?? null) ? $settings['system_status_content'] : [];
        $updatesContent = is_array($settings['system_updates_content'] ?? null) ? $settings['system_updates_content'] : [];

        return response()->view('pages.maintenance.index', [
            'systemSettings' => $settings,
            'pageMetaTitle' => ($settings['system_status_title'] ?: 'Bảo trì hệ thống').' | '.($settings['site_name'] ?: config('app.name', 'Nạp Tiền Tự Động')),
            'pageMetaDescription' => (string) ($settings['system_status_excerpt'] ?: $settings['site_description']),
            'maintenanceStatusHtml' => $contentRenderer->renderNodes($statusContent),
            'maintenanceUpdatesHtml' => $contentRenderer->renderNodes($updatesContent),
        ], 503);
    }
}
