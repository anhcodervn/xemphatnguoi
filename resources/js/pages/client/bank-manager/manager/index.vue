<template>
    <div class="space-y-3 pb-3">
        <PackageRequiredState v-if="!hasBankAccess" />

        <template v-else>
        <section class="rounded-[10px] border border-white/70 bg-white/75 px-4 py-3 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.16)] backdrop-blur">
            <RouterLink
                :to="{ name: 'client.bank-manager' }"
                class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 transition hover:text-blue-700"
            >
                <ArrowLeft class="h-4 w-4" />
                Quay lại danh sách thẻ
            </RouterLink>

            <h1 class="mt-2.5 text-[1.75rem] font-black tracking-[-0.04em] text-slate-900">Quản lý chi tiết thẻ</h1>
            <p class="mt-0.5 text-xs text-slate-500">Theo dõi liên kết ngân hàng, lịch sử giao dịch và webhook trực tiếp từ dữ liệu hệ thống.</p>
        </section>

        <section class="rounded-[10px] border border-slate-200/80 bg-white shadow-[0_14px_36px_-28px_rgba(15,23,42,0.18)]">
            <div class="border-b border-slate-100 px-3 py-3">
                <div class="no-scrollbar flex gap-2 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="min-w-fit rounded-[8px] border px-3 py-2 text-left transition-all"
                        :class="
                            activeTab === tab.key
                                ? 'border-slate-900 bg-slate-900 text-white'
                                : 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300 hover:bg-slate-100 hover:text-slate-900'
                        "
                        @click="activeTab = tab.key"
                    >
                        <div class="flex items-center gap-2">
                            <component :is="tab.icon" class="h-4 w-4" />
                            <div>
                                <p class="text-sm font-semibold">{{ tab.label }}</p>
                                <p class="mt-0.5 text-[11px]" :class="activeTab === tab.key ? 'text-slate-300' : 'text-slate-500'">
                                    {{ tab.description }}
                                </p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <div class="p-3">
                <div v-if="isLoadingAccount" class="space-y-3">
                    <div class="animate-pulse rounded-[10px] border border-slate-200 bg-white p-4">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 rounded-full bg-slate-100"></div>
                            <div class="flex-1 space-y-2">
                                <div class="h-4 w-48 rounded bg-slate-100"></div>
                                <div class="h-3 w-32 rounded bg-slate-100"></div>
                            </div>
                        </div>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                            <div v-for="item in 4" :key="item" class="h-20 rounded-[8px] bg-slate-100"></div>
                        </div>
                    </div>
                </div>

                <section v-else-if="account && activeTab === 'info'" class="grid gap-3 xl:grid-cols-[1.22fr_0.78fr]">
                    <div class="rounded-[10px] border border-slate-200/80 bg-white p-3.5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex min-w-0 gap-3">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-white text-xs font-bold text-white"
                                    :style="{ backgroundColor: account.bank_bg_color }"
                                >
                                    <img
                                        v-if="account.bank_logo"
                                        :src="account.bank_logo"
                                        :alt="account.bank_name"
                                        class="h-full w-full object-cover"
                                    />
                                    <span v-else>{{ bankInitials }}</span>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-base font-bold tracking-[-0.03em] text-slate-900">
                                            {{ account.bank_name }} - {{ account.account_name }}
                                        </h2>
                                        <span :class="statusBadgeClass(account.status)">
                                            <span class="bg-current/80 h-1.5 w-1.5 rounded-full" />
                                            {{ statusLabel(account.status) }}
                                        </span>
                                    </div>

                                    <p class="mt-1 text-xs tracking-[0.28em] text-slate-500">{{ maskedAccountNumber }}</p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500">
                                        <span class="inline-flex items-center gap-1.5">
                                            <UserRound class="h-3 w-3" />
                                            {{ account.username || 'Chưa có username' }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5">
                                            <CalendarClock class="h-3 w-3" />
                                            Cập nhật {{ formatDateTime(account.updated_at) }}
                                        </span>
                                        <span v-if="account.last_sync_at" class="inline-flex items-center gap-1.5">
                                            <RefreshCcw class="h-3 w-3" />
                                            Đồng bộ {{ formatDateTime(account.last_sync_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-2.5 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <p class="text-[11px] font-medium text-slate-400">Ngân hàng</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-900">{{ account.bank_full_name || account.bank_name }}</p>
                            </div>

                            <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <p class="text-[11px] font-medium text-slate-400">Mã ngân hàng</p>
                                <p class="mt-0.5 text-sm font-bold uppercase text-slate-900">{{ account.bank_code }}</p>
                            </div>

                            <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <p class="text-[11px] font-medium text-slate-400">Tên hiển thị</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-900">{{ account.account_name }}</p>
                            </div>

                            <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <p class="text-[11px] font-medium text-slate-400">Số tài khoản</p>
                                <p class="mt-0.5 text-sm font-bold text-slate-900">{{ account.account_number }}</p>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-2.5 sm:grid-cols-2">
                            <div class="rounded-[8px] border border-slate-200 bg-white px-3 py-3">
                                <p class="text-xs font-semibold text-slate-900">Thông tin kết nối</p>
                                <div class="mt-2 space-y-2 text-[11px] text-slate-500">
                                    <div class="flex items-center justify-between gap-3">
                                        <span>Trạng thái</span>
                                        <span class="font-semibold text-slate-700">{{ statusLabel(account.status) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span>Lần cập nhật gần nhất</span>
                                        <span class="font-semibold text-slate-700">{{ formatDateTime(account.updated_at) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span>Lần đồng bộ gần nhất</span>
                                        <span class="font-semibold text-slate-700">{{ formatDateTime(account.last_sync_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[8px] border border-slate-200 bg-white px-3 py-3">
                                <p class="text-xs font-semibold text-slate-900">Bảo mật dữ liệu</p>
                                <div class="mt-2 space-y-2 text-[11px] leading-5 text-slate-500">
                                    <div class="flex items-start gap-2">
                                        <ShieldCheck class="mt-0.5 h-3.5 w-3.5 shrink-0 text-blue-600" />
                                        <span>Trang này chỉ hiển thị dữ liệu cần thiết, không trả về mật khẩu hoặc token ngân hàng.</span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <Lock class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600" />
                                        <span>Thông tin đăng nhập được hệ thống quản lý riêng cho mục đích đồng bộ và xác thực kết nối.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside class="space-y-2.5 rounded-[10px] border border-slate-200/80 bg-white p-3.5">
                        <h3 class="text-base font-bold tracking-[-0.03em] text-slate-900">Tác vụ nhanh</h3>

                        <RouterLink
                            :to="{ name: 'client.bank-manager.bank.edit', params: { bank_id: currentBankId } }"
                            class="flex items-center justify-between rounded-[8px] border border-slate-200 bg-white px-3 py-2.5 text-left transition hover:bg-slate-50"
                        >
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-[8px] bg-slate-100 text-slate-600">
                                    <Pencil class="h-3.5 w-3.5" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-900">Chỉnh sửa thông tin</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">Cập nhật tên hiển thị, tài khoản đăng nhập hoặc mật khẩu</p>
                                </div>
                            </div>

                            <ChevronRight class="h-4 w-4 text-slate-300" />
                        </RouterLink>

                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-[8px] border border-rose-200 bg-white px-3 py-2.5 text-left transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isDeletingAccount"
                            @click="deleteAccount"
                        >
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 items-center justify-center rounded-[8px] bg-rose-100 text-rose-500">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-900">Xóa liên kết thẻ</p>
                                    <p class="mt-0.5 text-[11px] text-slate-500">Ngắt kết nối thẻ khỏi hệ thống và quay về danh sách quản lý</p>
                                </div>
                            </div>

                            <ChevronRight class="h-4 w-4 text-slate-300" />
                        </button>
                    </aside>
                </section>

                <section v-else-if="account && activeTab === 'transactions'" class="space-y-3">
                    <div class="rounded-[10px] border border-slate-200/80 bg-white px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-base font-bold tracking-[-0.03em] text-slate-900">Lịch sử giao dịch</h2>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Khi mở tab này, hệ thống sẽ gọi API đồng bộ giao dịch theo ngân hàng, lưu về database rồi trả lại danh sách mới
                                    nhất.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-[8px] border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="isLoadingTransactions"
                                    @click="refreshLatestTransactions"
                                >
                                    <RefreshCcw class="h-4 w-4" :class="isLoadingTransactions ? 'animate-spin' : ''" />
                                    {{ isLoadingTransactions ? 'Đang lấy...' : 'Lấy mới nhất' }}
                                </button>

                                <label class="flex items-center gap-2 text-xs font-medium text-slate-500">
                                    <span>Hiển thị</span>
                                    <select
                                        v-model.number="selectedTransactionLimit"
                                        class="rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                                    >
                                        <option v-for="limit in transactionLimitOptions" :key="limit" :value="limit">{{ limit }} giao dịch</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div v-if="isLoadingTransactions" class="space-y-2">
                        <div v-for="item in 4" :key="item" class="animate-pulse rounded-[8px] border border-slate-200 bg-white px-4 py-3">
                            <div class="grid grid-cols-[1.3fr_0.7fr_0.8fr_0.6fr] gap-3">
                                <div class="h-4 rounded bg-slate-100"></div>
                                <div class="h-4 rounded bg-slate-100"></div>
                                <div class="h-4 rounded bg-slate-100"></div>
                                <div class="h-4 rounded bg-slate-100"></div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="transactions.length === 0"
                        class="rounded-[10px] border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center"
                    >
                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                            <History class="h-5 w-5" />
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-900">Chưa có giao dịch nào</p>
                        <p class="mt-1 text-xs text-slate-500">API đã được gọi nhưng chưa lấy được lịch sử giao dịch cho liên kết thẻ này.</p>
                    </div>

                    <div v-else class="overflow-hidden rounded-[10px] border border-slate-200/80 bg-white">
                        <div
                            class="grid grid-cols-[1.25fr_0.65fr_0.8fr_0.5fr_0.7fr] gap-3 border-b border-slate-100 px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-400"
                        >
                            <span>Mô tả</span>
                            <span>Số tiền</span>
                            <span>Thời gian</span>
                            <span>Loại</span>
                            <span class="text-right">Thao tác</span>
                        </div>

                        <div
                            v-for="transaction in transactions"
                            :key="transaction.transaction_id"
                            class="grid grid-cols-[1.25fr_0.65fr_0.8fr_0.5fr_0.7fr] gap-3 border-b border-slate-100 px-4 py-3 last:border-b-0"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">
                                    {{ transaction.description || 'Không có mô tả giao dịch' }}
                                </p>
                                <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ transaction.transaction_id }}</p>
                            </div>

                            <div class="text-sm font-semibold" :class="transactionAmountClass(transaction.type)">
                                {{ formatAmount(transaction.amount, transaction.type) }}
                            </div>

                            <div class="text-[11px] text-slate-500">{{ formatDateTime(transaction.transaction_time) }}</div>

                            <div>
                                <span :class="transactionTypeBadgeClass(transaction.type)">
                                    {{ transactionTypeLabel(transaction.type) }}
                                </span>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button
                                    type="button"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-[8px] border px-2.5 text-[11px] font-semibold transition disabled:cursor-not-allowed disabled:opacity-60"
                                    :class="
                                        canDispatchTransactionCallback(transaction)
                                            ? 'border-blue-200 bg-blue-50 text-blue-700 hover:border-blue-300 hover:bg-blue-100'
                                            : 'border-slate-200 bg-slate-100 text-slate-400'
                                    "
                                    :disabled="!canDispatchTransactionCallback(transaction) || dispatchingTransactionId === transaction.id"
                                    @click="dispatchTransactionCallback(transaction)"
                                >
                                    <Webhook class="h-3.5 w-3.5" :class="dispatchingTransactionId === transaction.id ? 'animate-pulse' : ''" />
                                    {{ dispatchingTransactionId === transaction.id ? 'Đang gửi' : 'Callback' }}
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-[8px] border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700"
                                    @click="openTransactionDetail(transaction)"
                                >
                                    <Eye class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section v-else-if="account && activeTab === 'webhooks'" class="space-y-3">
                    <div class="rounded-[10px] border border-slate-200/80 bg-white px-4 py-3">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h2 class="text-base font-bold tracking-[-0.03em] text-slate-900">Webhook theo từ khóa</h2>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Để trống từ khóa nếu muốn nhận toàn bộ giao dịch tiền vào. Nếu có từ khóa, hệ thống chỉ gửi callback khi nội
                                    dung giao dịch chứa đúng từ khóa đó.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-[8px] bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                                @click="openWebhookModal()"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                Thêm webhook
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-2.5 lg:grid-cols-3">
                        <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">Bank ID</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ account.id }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">Gửi kèm trong body để bên nhận callback đối chiếu đúng thẻ.</p>
                        </div>

                        <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">Secret Header</p>
                            <p class="mt-1 break-all text-sm font-bold text-slate-900">X-Webhook-Secret</p>
                            <p class="mt-1 text-[11px] text-slate-500">Header này sẽ chứa secret riêng của từng webhook khi hệ thống gửi đi.</p>
                        </div>

                        <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <p class="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-400">Sign</p>
                            <p class="mt-1 break-all font-mono text-[13px] font-bold text-slate-900">md5(secret_key + bank_id)</p>
                            <p class="mt-1 text-[11px] text-slate-500">Bên nhận callback dùng công thức này để verify payload.</p>
                        </div>
                    </div>

                    <div v-if="isLoadingWebhooks" class="space-y-2">
                        <div v-for="item in 3" :key="item" class="animate-pulse rounded-[10px] border border-slate-200 bg-white px-4 py-3">
                            <div class="space-y-2">
                                <div class="h-4 w-40 rounded bg-slate-100"></div>
                                <div class="h-3 w-full rounded bg-slate-100"></div>
                                <div class="h-3 w-32 rounded bg-slate-100"></div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="webhooks.length === 0"
                        class="rounded-[10px] border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center"
                    >
                        <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                            <Webhook class="h-5 w-5" />
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-900">Chưa có webhook nào</p>
                        <p class="mt-1 text-xs text-slate-500">Tạo webhook đầu tiên để hệ thống gửi callback khi có giao dịch phù hợp.</p>
                    </div>

                    <div v-else class="space-y-2.5">
                        <article v-for="webhook in webhooks" :key="webhook.id" class="rounded-[10px] border border-slate-200/80 bg-white px-4 py-3">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate text-sm font-bold text-slate-900">{{ webhook.name || 'Webhook không tên' }}</h3>
                                        <span :class="webhookStatusBadgeClass(webhook.status)">
                                            {{ webhook.status === 'active' ? 'Đang bật' : 'Tạm dừng' }}
                                        </span>
                                    </div>
                                    <p class="mt-1 truncate text-xs text-slate-500">{{ webhook.url }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-700">
                                            Từ khóa: {{ webhookEventLabel(webhook.event_keyword) }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="truncate">
                                                Secret:
                                                {{
                                                    visibleWebhookSecrets[webhook.id]
                                                        ? (revealedWebhookSecrets[webhook.id] ?? '')
                                                        : (webhook.secret_key_masked ?? maskWebhookSecret(''))
                                                }}
                                            </span>
                                            <button
                                                type="button"
                                                class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                                                :disabled="loadingWebhookSecrets[webhook.id]"
                                                @click="toggleWebhookSecret(webhook)"
                                            >
                                                <component :is="visibleWebhookSecrets[webhook.id] ? EyeOff : Eye" class="h-3 w-3" />
                                            </button>
                                        </span>
                                        <span class="truncate">Sign: md5(secret_key + {{ webhook.bank_account_id }})</span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="rounded-[8px] border border-emerald-200 bg-white px-3 py-2 text-xs font-medium text-emerald-600 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60"
                                        :disabled="dispatchingWebhookId === webhook.id"
                                        @click="dispatchWebhook(webhook)"
                                    >
                                        {{ dispatchingWebhookId === webhook.id ? 'Đang đưa vào queue...' : 'Gửi thử' }}
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-[8px] border border-blue-200 bg-white px-3 py-2 text-xs font-medium text-blue-600 transition hover:bg-blue-50"
                                        @click="openWebhookLogs(webhook)"
                                    >
                                        Xem log
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                        @click="openWebhookModal(webhook)"
                                    >
                                        Sửa
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-[8px] border border-rose-200 bg-white px-3 py-2 text-xs font-medium text-rose-500 transition hover:bg-rose-50"
                                        @click="deleteWebhook(webhook)"
                                    >
                                        Xóa
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section v-else class="rounded-[10px] border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                        <CreditCard class="h-5 w-5" />
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-900">Không tìm thấy liên kết thẻ</p>
                    <p class="mt-1 text-xs text-slate-500">Bản ghi có thể đã bị xóa hoặc không còn khả dụng.</p>
                </section>
            </div>
        </section>

        <Modal :modelValue="isWebhookModalOpen" panelClass="max-w-2xl" @update:modelValue="closeWebhookModal">
            <template #header>
                <div class="border-b border-slate-200 px-4 py-3 pr-12">
                    <h3 class="text-base font-semibold text-slate-900">{{ editingWebhookId ? 'Cập nhật webhook' : 'Tạo webhook mới' }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Khai báo URL nhận dữ liệu. Để trống từ khóa nếu muốn nhận toàn bộ giao dịch tiền vào.</p>
                </div>
            </template>

            <div class="space-y-3 px-4 pb-4 pt-1">
                <label class="space-y-1.5">
                    <span class="text-xs font-semibold text-slate-600">Tên webhook</span>
                    <input
                        v-model="webhookForm.name"
                        type="text"
                        class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                        placeholder="Ví dụ: Webhook đơn hàng ACB"
                    />
                </label>

                <div class="rounded-[8px] border border-blue-200 bg-blue-50 px-3 py-3 text-[11px] leading-5 text-blue-900">
                    <p class="font-semibold">Quy cách callback</p>
                    <div class="mt-2 space-y-1">
                        <p>Header: <span class="font-mono">X-Webhook-Secret: &lt;secret_key&gt;</span></p>
                        <p>Body: <span class="font-mono">bank_id = {{ account?.id ?? '---' }}</span></p>
                        <p>Sign: <span class="font-mono">md5(secret_key + bank_id)</span></p>
                    </div>
                </div>

                <label class="space-y-1.5">
                    <span class="text-xs font-semibold text-slate-600">URL nhận webhook</span>
                    <input
                        v-model="webhookForm.url"
                        type="text"
                        class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                        placeholder="https://client.example.com/webhook/bank"
                    />
                </label>

                <label class="space-y-1.5">
                    <span class="text-xs font-semibold text-slate-600">Từ khóa event</span>
                    <input
                        v-model="webhookForm.event_keyword"
                        type="text"
                        class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                        placeholder="Để trống để nhận toàn bộ giao dịch tiền vào"
                    />
                    <p class="text-[11px] text-slate-500">Ví dụ: nhập `napabc` nếu chỉ muốn nhận giao dịch có chứa từ khóa đó.</p>
                </label>

                <label class="space-y-1.5">
                    <span class="text-xs font-semibold text-slate-600">Trạng thái</span>
                    <select
                        v-model="webhookForm.status"
                        class="w-full rounded-[8px] border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="active">Đang bật</option>
                        <option value="inactive">Tạm dừng</option>
                    </select>
                </label>
            </div>

            <template #footer>
                <div class="border-t border-slate-200 px-4 py-3">
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-[8px] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                            @click="closeWebhookModal"
                        >
                            Hủy
                        </button>
                        <button
                            type="button"
                            class="rounded-[8px] bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isSavingWebhook"
                            @click="submitWebhook"
                        >
                            {{ isSavingWebhook ? 'Đang lưu...' : editingWebhookId ? 'Lưu thay đổi' : 'Tạo webhook' }}
                        </button>
                    </div>
                </div>
            </template>
        </Modal>

        <Modal :modelValue="isWebhookLogsOpen" panelClass="max-w-6xl" @update:modelValue="closeWebhookLogs">
            <template #header>
                <div class="border-b border-slate-200 px-4 py-3 pr-12">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Lịch sử gửi webhook</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ selectedWebhookForLogs?.name || selectedWebhookForLogs?.url || 'Webhook' }}</p>
                        </div>

                        <button
                            type="button"
                            class="rounded-[8px] border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="isLoadingWebhookLogs || !selectedWebhookForLogs"
                            @click="refreshWebhookLogs"
                        >
                            {{ isLoadingWebhookLogs ? 'Đang tải...' : 'Tải lại log' }}
                        </button>
                    </div>
                </div>
            </template>

            <div class="space-y-3 px-4 pb-4 pt-1">
                <div v-if="isLoadingWebhookLogs" class="space-y-2">
                    <div v-for="item in 3" :key="item" class="animate-pulse rounded-[8px] border border-slate-200 p-3">
                        <div class="space-y-2">
                            <div class="h-4 w-32 rounded bg-slate-100"></div>
                            <div class="h-3 w-full rounded bg-slate-100"></div>
                            <div class="h-3 w-2/3 rounded bg-slate-100"></div>
                        </div>
                    </div>
                </div>

                <div
                    v-else-if="webhookLogs.length === 0"
                    class="rounded-[8px] border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500"
                >
                    Chưa có log gửi webhook nào. Nếu vừa bấm gửi thử, chờ vài giây để queue xử lý rồi tải lại.
                </div>

                <div v-else class="grid max-h-[70vh] gap-3 lg:grid-cols-[280px_minmax(0,1fr)]">
                    <div class="overflow-y-auto rounded-[10px] border border-slate-200 bg-slate-50 p-2">
                        <button
                            v-for="log in webhookLogs"
                            :key="log.id"
                            type="button"
                            class="flex w-full flex-col gap-2 rounded-[8px] border px-3 py-3 text-left transition"
                            :class="
                                selectedWebhookLog?.id === log.id
                                    ? 'border-slate-900 bg-slate-900 text-white shadow-sm'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'
                            "
                            @click="selectedWebhookLogId = log.id"
                        >
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    :class="selectedWebhookLog?.id === log.id ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-700'"
                                >
                                    Attempt {{ log.attempt }}
                                </span>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    :class="selectedWebhookLog?.id === log.id ? 'bg-white/15 text-white' : webhookLogStatusClass(log.status_code)"
                                >
                                    {{ webhookLogStatusLabel(log.status_code) }}
                                </span>
                            </div>
                            <p class="text-xs font-semibold" :class="selectedWebhookLog?.id === log.id ? 'text-white' : 'text-slate-900'">
                                {{ formatDateTime(log.created_at) }}
                            </p>
                            <p class="text-[11px]" :class="selectedWebhookLog?.id === log.id ? 'text-slate-200' : 'text-slate-500'">
                                {{ webhookEventLabel(log.event_keyword) }}
                            </p>
                        </button>
                    </div>

                    <div v-if="selectedWebhookLog" class="overflow-y-auto rounded-[10px] border border-slate-200 bg-white p-3">
                        <div class="grid gap-2 sm:grid-cols-3">
                            <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <p class="text-[11px] font-medium text-slate-400">Attempt</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ selectedWebhookLog.attempt }}</p>
                            </div>
                            <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <p class="text-[11px] font-medium text-slate-400">Trạng thái</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ webhookLogStatusLabel(selectedWebhookLog.status_code) }}</p>
                            </div>
                            <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <p class="text-[11px] font-medium text-slate-400">Thời gian</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ formatDateTime(selectedWebhookLog.created_at) }}</p>
                            </div>
                        </div>

                        <div class="mt-3 rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <p class="text-[11px] font-medium text-slate-400">Keyword</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ webhookEventLabel(selectedWebhookLog.event_keyword) }}</p>
                        </div>

                        <div class="mt-3 grid gap-3 xl:grid-cols-2">
                            <div class="rounded-[8px] bg-slate-50 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Payload</p>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-[6px] border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50"
                                            @click="copyWebhookJson(selectedWebhookLogPayloadText, 'payload')"
                                        >
                                            <component :is="copiedWebhookJsonKey === 'payload' ? CheckCheck : Copy" class="h-3.5 w-3.5" />
                                            {{ copiedWebhookJsonKey === 'payload' ? 'Đã copy' : 'Copy' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-[6px] border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50"
                                            @click="isWebhookPayloadExpanded = !isWebhookPayloadExpanded"
                                        >
                                            <component :is="isWebhookPayloadExpanded ? Minimize2 : Maximize2" class="h-3.5 w-3.5" />
                                            {{ isWebhookPayloadExpanded ? 'Thu gọn' : 'Mở rộng' }}
                                        </button>
                                    </div>
                                </div>
                                <textarea
                                    :value="selectedWebhookLogPayloadText"
                                    readonly
                                    spellcheck="false"
                                    class="mt-2 w-full resize-y overflow-auto rounded-[6px] border border-slate-200 bg-white p-2 font-mono text-[11px] leading-5 text-slate-700 outline-none"
                                    :class="isWebhookPayloadExpanded ? 'min-h-[22rem]' : 'min-h-12'"
                                ></textarea>
                            </div>

                            <div class="rounded-[8px] bg-slate-50 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">
                                        Response{{ selectedWebhookLog.status_code ? ` (${selectedWebhookLog.status_code})` : '' }}
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-[6px] border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50"
                                            @click="copyWebhookJson(selectedWebhookLogResponseText, 'response')"
                                        >
                                            <component :is="copiedWebhookJsonKey === 'response' ? CheckCheck : Copy" class="h-3.5 w-3.5" />
                                            {{ copiedWebhookJsonKey === 'response' ? 'Đã copy' : 'Copy' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-[6px] border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50"
                                            @click="isWebhookResponseExpanded = !isWebhookResponseExpanded"
                                        >
                                            <component :is="isWebhookResponseExpanded ? Minimize2 : Maximize2" class="h-3.5 w-3.5" />
                                            {{ isWebhookResponseExpanded ? 'Thu gọn' : 'Mở rộng' }}
                                        </button>
                                    </div>
                                </div>
                                <textarea
                                    :value="selectedWebhookLogResponseText"
                                    readonly
                                    spellcheck="false"
                                    class="mt-2 w-full resize-y overflow-auto rounded-[6px] border border-slate-200 bg-white p-2 font-mono text-[11px] leading-5 text-slate-700 outline-none"
                                    :class="isWebhookResponseExpanded ? 'min-h-[22rem]' : 'min-h-12'"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template #footer>
                <div class="border-t border-slate-200 px-4 py-3">
                    <div class="flex justify-end">
                        <button
                            type="button"
                            class="rounded-[8px] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                            @click="closeWebhookLogs"
                        >
                            Đóng
                        </button>
                    </div>
                </div>
            </template>
        </Modal>

        <Modal :modelValue="isTransactionDetailOpen" panelClass="max-w-3xl" @update:modelValue="closeTransactionDetail">
            <template #header>
                <div class="border-b border-slate-200 px-4 py-3 pr-12">
                    <h3 class="text-base font-semibold text-slate-900">Chi tiết giao dịch</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ selectedTransaction?.transaction_id || '---' }}</p>
                </div>
            </template>

            <div class="space-y-3 px-4 pb-4 pt-2">
                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] font-medium text-slate-400">Số tiền</p>
                        <p class="mt-0.5 text-sm font-bold text-slate-900">
                            {{ selectedTransaction ? formatAmount(selectedTransaction.amount, selectedTransaction.type) : '---' }}
                        </p>
                    </div>

                    <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                        <p class="text-[11px] font-medium text-slate-400">Thời gian</p>
                        <p class="mt-0.5 text-sm font-bold text-slate-900">
                            {{ selectedTransaction ? formatDateTime(selectedTransaction.transaction_time) : '---' }}
                        </p>
                    </div>
                </div>

                <div class="rounded-[8px] border border-slate-200 bg-slate-50 px-3 py-2.5">
                    <p class="text-[11px] font-medium text-slate-400">Mô tả</p>
                    <p class="mt-0.5 text-sm font-semibold text-slate-900">
                        {{ selectedTransaction?.description || 'Không có mô tả giao dịch' }}
                    </p>
                </div>

                <div class="rounded-[8px] border border-slate-200 bg-slate-50 p-3">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Raw data</p>
                    <pre class="mt-2 max-h-72 overflow-auto whitespace-pre-wrap break-all rounded-[6px] bg-white p-2 text-[11px] text-slate-700">{{
                        selectedTransactionRawData
                    }}</pre>
                </div>
            </div>

            <template #footer>
                <div class="border-t border-slate-200 px-4 py-3">
                    <div class="flex justify-end">
                        <button
                            type="button"
                            class="rounded-[8px] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                            @click="closeTransactionDetail"
                        >
                            Đóng
                        </button>
                    </div>
                </div>
            </template>
        </Modal>
        </template>
    </div>
</template>

<script setup lang="ts">
import Modal from '@/components/shared/Modal/index.vue';
import { clientBankService } from '@/services/client-bank.service';
import { clientWebhookService } from '@/services/client-webhook.service';
import { useUserStore } from '@/stores/user.store';
import type { BankAccountType, BankTransactionType, WebhookLogType, WebhookType } from '@/types/bank.type';
import { handleErrorResponse } from '@/utils/response';
import {
    ArrowLeft,
    CalendarClock,
    CheckCheck,
    ChevronRight,
    Copy,
    CreditCard,
    Eye,
    EyeOff,
    History,
    Lock,
    Maximize2,
    Minimize2,
    Pencil,
    Plus,
    RefreshCcw,
    ShieldCheck,
    Trash2,
    UserRound,
    Webhook,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import PackageRequiredState from '../components/PackageRequiredState.vue';

type TabKey = 'info' | 'transactions' | 'webhooks';
type WebhookStatus = 'active' | 'inactive';

const route = useRoute();
const router = useRouter();
const userStore = useUserStore();

const currentBankId = computed(() => String(route.params.bank_id ?? ''));
const activeTab = ref<TabKey>('info');
const account = ref<BankAccountType | null>(null);
const transactions = ref<BankTransactionType[]>([]);
const webhooks = ref<WebhookType[]>([]);
const webhookLogs = ref<WebhookLogType[]>([]);
const visibleWebhookSecrets = ref<Record<number, boolean>>({});
const revealedWebhookSecrets = ref<Record<number, string>>({});
const loadingWebhookSecrets = ref<Record<number, boolean>>({});
const selectedTransaction = ref<BankTransactionType | null>(null);
const selectedWebhookForLogs = ref<WebhookType | null>(null);
const selectedWebhookLogId = ref<number | null>(null);
const copiedWebhookJsonKey = ref<'payload' | 'response' | null>(null);
const isLoadingAccount = ref(false);
const isLoadingTransactions = ref(false);
const isLoadingWebhooks = ref(false);
const isLoadingWebhookLogs = ref(false);
const isDeletingAccount = ref(false);
const isSavingWebhook = ref(false);
const isWebhookModalOpen = ref(false);
const isWebhookLogsOpen = ref(false);
const isTransactionDetailOpen = ref(false);
const isWebhookPayloadExpanded = ref(false);
const isWebhookResponseExpanded = ref(false);
const editingWebhookId = ref<number | null>(null);
const dispatchingWebhookId = ref<number | null>(null);
const dispatchingTransactionId = ref<number | null>(null);
const transactionLimitOptions = [10, 20, 50, 100];
const selectedTransactionLimit = ref(20);
let copiedWebhookJsonTimer: ReturnType<typeof setTimeout> | null = null;
const hasBankAccess = computed(() => {
    const subscription = userStore.user?.user_subscriptions;

    if (!subscription || subscription.status !== 'active') {
        return false;
    }

    if (!subscription.expires_at) {
        return true;
    }

    return new Date(subscription.expires_at).getTime() > Date.now();
});

const tabs: Array<{
    key: TabKey;
    label: string;
    description: string;
    icon: unknown;
}> = [
    { key: 'info', label: 'Thông tin thẻ', description: 'Dữ liệu liên kết đã lưu', icon: CreditCard },
    { key: 'transactions', label: 'Lịch sử giao dịch', description: 'Đồng bộ khi mở tab', icon: History },
    { key: 'webhooks', label: 'Webhook', description: 'Queue và log gửi', icon: Webhook },
];

const defaultWebhookForm = (): {
    name: string;
    url: string;
    event_keyword: string;
    status: WebhookStatus;
} => ({
    name: '',
    url: '',
    event_keyword: '',
    status: 'active',
});

const webhookForm = reactive(defaultWebhookForm());

const bankInitials = computed(() => {
    const value = account.value;

    if (!value) {
        return 'BK';
    }

    return (value.bank_short_name || value.bank_name || value.bank_code || 'BK')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
});

const maskedAccountNumber = computed(() => {
    const accountNumber = account.value?.account_number?.trim() ?? '';

    if (accountNumber.length <= 4) {
        return accountNumber;
    }

    return `**** **** ${accountNumber.slice(-4)}`;
});

const statusLabel = (status: BankAccountType['status']): string => {
    switch (status) {
        case 'active':
            return 'Đang hoạt động';
        case 'inactive':
            return 'Ngưng hoạt động';
        case 'error':
            return 'Lỗi kết nối';
        default:
            return status;
    }
};

const statusBadgeClass = (status: BankAccountType['status']): string => {
    switch (status) {
        case 'active':
            return 'inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700';
        case 'inactive':
            return 'inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700';
        case 'error':
            return 'inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-700';
        default:
            return 'inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700';
    }
};

const transactionTypeLabel = (type: BankTransactionType['type']): string => {
    switch (type) {
        case 'credit':
            return 'Tiền vào';
        case 'debit':
            return 'Tiền ra';
        default:
            return 'Không rõ';
    }
};

const transactionTypeBadgeClass = (type: BankTransactionType['type']): string => {
    switch (type) {
        case 'credit':
            return 'inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700';
        case 'debit':
            return 'inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-semibold text-rose-700';
        default:
            return 'inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700';
    }
};

const transactionAmountClass = (type: BankTransactionType['type']): string => {
    if (type === 'credit') {
        return 'text-emerald-600';
    }

    if (type === 'debit') {
        return 'text-rose-600';
    }

    return 'text-slate-900';
};

const canDispatchTransactionCallback = (transaction: BankTransactionType): boolean => {
    return Boolean(transaction.id) && transaction.type === 'credit';
};

const webhookStatusBadgeClass = (status: WebhookType['status']): string => {
    return status === 'active'
        ? 'inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700'
        : 'inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700';
};

const webhookEventLabel = (eventKeyword: string | null): string => {
    if (!eventKeyword || eventKeyword.trim() === '') {
        return 'Tất cả giao dịch tiền vào';
    }

    return eventKeyword;
};

const webhookLogStatusClass = (statusCode: number | null): string => {
    if (statusCode !== null && statusCode >= 200 && statusCode < 300) {
        return 'bg-emerald-100 text-emerald-700';
    }

    if (statusCode !== null) {
        return 'bg-rose-100 text-rose-700';
    }

    return 'bg-amber-100 text-amber-700';
};

const webhookLogStatusLabel = (statusCode: number | null): string => {
    if (statusCode !== null && statusCode >= 200 && statusCode < 300) {
        return 'Thành công';
    }

    if (statusCode !== null) {
        return 'Lỗi phản hồi';
    }

    return 'Lỗi kết nối';
};

const toggleWebhookSecret = async (webhook: WebhookType): Promise<void> => {
    if (visibleWebhookSecrets.value[webhook.id]) {
        visibleWebhookSecrets.value = {
            ...visibleWebhookSecrets.value,
            [webhook.id]: false,
        };

        return;
    }

    if (!revealedWebhookSecrets.value[webhook.id]) {
        loadingWebhookSecrets.value = {
            ...loadingWebhookSecrets.value,
            [webhook.id]: true,
        };

        try {
            const secretKey = await clientWebhookService.revealSecret(webhook.id);

            revealedWebhookSecrets.value = {
                ...revealedWebhookSecrets.value,
                [webhook.id]: secretKey,
            };
        } catch (error) {
            handleErrorResponse(error);
            return;
        } finally {
            loadingWebhookSecrets.value = {
                ...loadingWebhookSecrets.value,
                [webhook.id]: false,
            };
        }
    }

    visibleWebhookSecrets.value = {
        ...visibleWebhookSecrets.value,
        [webhook.id]: true,
    };
};

const maskWebhookSecret = (secret: string): string => {
    if (secret.length <= 6) {
        return '••••••';
    }

    return `${secret.slice(0, 3)}••••••${secret.slice(-3)}`;
};

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return 'chưa có dữ liệu';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};

const formatAmount = (amount: string, type: BankTransactionType['type']): string => {
    const numericAmount = Number(amount);

    if (Number.isNaN(numericAmount)) {
        return amount;
    }

    const prefix = type === 'debit' ? '-' : type === 'credit' ? '+' : '';

    return `${prefix}${new Intl.NumberFormat('vi-VN').format(numericAmount)}đ`;
};

const selectedTransactionRawData = computed((): string => {
    const rawData = selectedTransaction.value?.raw_data;

    if (!rawData) {
        return '{}';
    }

    try {
        return JSON.stringify(rawData, null, 2);
    } catch {
        return String(rawData);
    }
});

const selectedWebhookLog = computed<WebhookLogType | null>(() => {
    if (webhookLogs.value.length === 0) {
        return null;
    }

    if (selectedWebhookLogId.value === null) {
        return webhookLogs.value[0] ?? null;
    }

    return webhookLogs.value.find((log) => log.id === selectedWebhookLogId.value) ?? webhookLogs.value[0] ?? null;
});

const formatWebhookJson = (value: string | null): string => {
    if (!value || value.trim() === '') {
        return '-';
    }

    try {
        return JSON.stringify(JSON.parse(value), null, 2);
    } catch {
        return value;
    }
};

const selectedWebhookLogPayloadText = computed((): string => {
    return formatWebhookJson(selectedWebhookLog.value?.payload ?? null);
});

const selectedWebhookLogResponseText = computed((): string => {
    return formatWebhookJson(selectedWebhookLog.value?.response ?? null);
});

const copyWebhookJson = async (value: string, key: 'payload' | 'response'): Promise<void> => {
    await navigator.clipboard.writeText(value);
    copiedWebhookJsonKey.value = key;

    if (copiedWebhookJsonTimer) {
        clearTimeout(copiedWebhookJsonTimer);
    }

    copiedWebhookJsonTimer = setTimeout(() => {
        copiedWebhookJsonKey.value = null;
    }, 1500);
};

const resetWebhookForm = (): void => {
    Object.assign(webhookForm, defaultWebhookForm());
    editingWebhookId.value = null;
};

const openWebhookModal = (webhook?: WebhookType): void => {
    if (webhook) {
        editingWebhookId.value = webhook.id;
        Object.assign(webhookForm, {
            name: webhook.name ?? '',
            url: webhook.url,
            event_keyword: webhook.event_keyword ?? '',
            status: webhook.status,
        });
    } else {
        resetWebhookForm();
    }

    isWebhookModalOpen.value = true;
};

const closeWebhookModal = (): void => {
    isWebhookModalOpen.value = false;
    resetWebhookForm();
};

const loadAccount = async (): Promise<void> => {
    if (currentBankId.value === '') {
        account.value = null;
        return;
    }

    isLoadingAccount.value = true;

    try {
        account.value = await clientBankService.getAccount(currentBankId.value);
    } catch (error) {
        account.value = null;
        handleErrorResponse(error);
        await router.push({ name: 'client.bank-manager' });
    } finally {
        isLoadingAccount.value = false;
    }
};

const loadTransactions = async (forceRefresh = false): Promise<void> => {
    if (currentBankId.value === '' || isLoadingTransactions.value) {
        return;
    }

    isLoadingTransactions.value = true;

    try {
        transactions.value = await clientBankService.listTransactions(currentBankId.value, selectedTransactionLimit.value, forceRefresh);
        await loadAccount();
    } catch (error) {
        transactions.value = [];
        handleErrorResponse(error);
    } finally {
        isLoadingTransactions.value = false;
    }
};

const refreshLatestTransactions = async (): Promise<void> => {
    await loadTransactions(true);
};

const loadWebhooks = async (): Promise<void> => {
    if (currentBankId.value === '' || isLoadingWebhooks.value) {
        return;
    }

    isLoadingWebhooks.value = true;

    try {
        webhooks.value = await clientWebhookService.listByBank(currentBankId.value);
        visibleWebhookSecrets.value = {};
        revealedWebhookSecrets.value = {};
        loadingWebhookSecrets.value = {};
    } catch (error) {
        webhooks.value = [];
        visibleWebhookSecrets.value = {};
        revealedWebhookSecrets.value = {};
        loadingWebhookSecrets.value = {};
        handleErrorResponse(error);
    } finally {
        isLoadingWebhooks.value = false;
    }
};

const loadWebhookLogs = async (webhookId: number): Promise<void> => {
    isLoadingWebhookLogs.value = true;

    try {
        webhookLogs.value = await clientWebhookService.logs(webhookId);
        selectedWebhookLogId.value = webhookLogs.value[0]?.id ?? null;
        isWebhookPayloadExpanded.value = false;
        isWebhookResponseExpanded.value = false;
    } catch (error) {
        webhookLogs.value = [];
        selectedWebhookLogId.value = null;
        handleErrorResponse(error);
    } finally {
        isLoadingWebhookLogs.value = false;
    }
};

const openWebhookLogs = async (webhook: WebhookType): Promise<void> => {
    selectedWebhookForLogs.value = webhook;
    isWebhookLogsOpen.value = true;
    await loadWebhookLogs(webhook.id);
};

const closeWebhookLogs = (): void => {
    isWebhookLogsOpen.value = false;
    selectedWebhookForLogs.value = null;
    selectedWebhookLogId.value = null;
    copiedWebhookJsonKey.value = null;
    isWebhookPayloadExpanded.value = false;
    isWebhookResponseExpanded.value = false;
    webhookLogs.value = [];
};

const openTransactionDetail = (transaction: BankTransactionType): void => {
    selectedTransaction.value = transaction;
    isTransactionDetailOpen.value = true;
};

const closeTransactionDetail = (): void => {
    isTransactionDetailOpen.value = false;
    selectedTransaction.value = null;
};

const refreshWebhookLogs = async (): Promise<void> => {
    if (!selectedWebhookForLogs.value) {
        return;
    }

    await loadWebhookLogs(selectedWebhookForLogs.value.id);
};

const submitWebhook = async (): Promise<void> => {
    if (currentBankId.value === '' || isSavingWebhook.value) {
        return;
    }

    isSavingWebhook.value = true;

    try {
        const payload = {
            name: webhookForm.name,
            url: webhookForm.url,
            event_keyword: webhookForm.event_keyword,
            status: webhookForm.status,
        };

        const response = editingWebhookId.value
            ? await clientWebhookService.update(editingWebhookId.value, payload)
            : await clientWebhookService.create(currentBankId.value, payload);

        await Swal.fire(
            'Thành công',
            response.data.message || (editingWebhookId.value ? 'Cập nhật webhook thành công.' : 'Tạo webhook thành công.'),
            'success',
        );

        closeWebhookModal();
        await loadWebhooks();
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        isSavingWebhook.value = false;
    }
};

const dispatchWebhook = async (webhook: WebhookType): Promise<void> => {
    if (currentBankId.value === '' || dispatchingWebhookId.value === webhook.id) {
        return;
    }

    dispatchingWebhookId.value = webhook.id;

    try {
        const response = await clientWebhookService.dispatch(currentBankId.value, {
            event_keyword: webhook.event_keyword ?? '',
            payload: {
                source: 'client-bank-manager',
                webhook_id: webhook.id,
                bank_account_id: webhook.bank_account_id,
            },
        });

        await Swal.fire('Đã đưa vào queue', response.data.message || 'Webhook đã được đưa vào hàng chờ.', 'success');
        await openWebhookLogs(webhook);
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        dispatchingWebhookId.value = null;
    }
};

const dispatchTransactionCallback = async (transaction: BankTransactionType): Promise<void> => {
    if (currentBankId.value === '' || !canDispatchTransactionCallback(transaction) || dispatchingTransactionId.value === transaction.id) {
        return;
    }

    dispatchingTransactionId.value = transaction.id;

    try {
        const response = await clientWebhookService.dispatchTransaction(currentBankId.value, transaction.id);
        const dispatchedCount = Number(response.data?.data?.dispatched_count ?? 0);

        await Swal.fire(
            dispatchedCount > 0 ? 'Đã đưa vào queue' : 'Không có webhook phù hợp',
            response.data?.message || (dispatchedCount > 0 ? 'Webhook đã được đưa vào hàng chờ.' : 'Không tìm thấy webhook phù hợp với giao dịch này.'),
            dispatchedCount > 0 ? 'success' : 'info',
        );
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        dispatchingTransactionId.value = null;
    }
};

const deleteWebhook = async (webhook: WebhookType): Promise<void> => {
    const confirmation = await Swal.fire({
        icon: 'warning',
        title: 'Xóa webhook?',
        text: `Webhook ${webhook.name || webhook.url} sẽ bị xóa khỏi thẻ này.`,
        showCancelButton: true,
        confirmButtonText: 'Xóa webhook',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#dc2626',
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    try {
        const response = await clientWebhookService.delete(webhook.id);
        await Swal.fire('Thành công', response.data.message || 'Xóa webhook thành công.', 'success');
        await loadWebhooks();
    } catch (error) {
        handleErrorResponse(error);
    }
};

const deleteAccount = async (): Promise<void> => {
    if (!account.value || isDeletingAccount.value) {
        return;
    }

    const confirmation = await Swal.fire({
        icon: 'warning',
        title: 'Xóa liên kết thẻ?',
        text: `Liên kết ${account.value.bank_name} - ${account.value.account_name} sẽ bị xóa khỏi hệ thống.`,
        showCancelButton: true,
        confirmButtonText: 'Xóa liên kết',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#dc2626',
    });

    if (!confirmation.isConfirmed) {
        return;
    }

    isDeletingAccount.value = true;

    try {
        const response = await clientBankService.deleteAccount(account.value.id);
        await Swal.fire('Thành công', response.data.message || 'Xóa liên kết thẻ thành công.', 'success');
        await router.push({ name: 'client.bank-manager' });
    } catch (error) {
        handleErrorResponse(error);
    } finally {
        isDeletingAccount.value = false;
    }
};

watch(activeTab, async (tab) => {
    if (tab === 'transactions') {
        await loadTransactions();
        return;
    }

    if (tab === 'webhooks') {
        await loadWebhooks();
    }
});

watch(selectedTransactionLimit, async () => {
    if (activeTab.value === 'transactions') {
        await loadTransactions();
    }
});

onMounted(async () => {
    if (!userStore.user) {
        await userStore.bootstrap({ silent: true });
    }

    if (!hasBankAccess.value) {
        return;
    }

    await loadAccount();
});
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
