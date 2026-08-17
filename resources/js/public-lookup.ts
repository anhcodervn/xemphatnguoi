type ResolutionStatus = 'processed' | 'unprocessed' | 'unknown';

type LookupViolation = {
    plate_color: string | null;
    time: string | null;
    location: string | null;
    behavior: string | null;
    status: string | null;
    resolution_status: ResolutionStatus;
    agency: string | null;
    resolution_agency: string | null;
    resolution_address: string | null;
    resolution_phone: string | null;
};

type LookupData = {
    plate: string;
    display_plate: string;
    vehicle_type: string;
    status: 'success' | 'no_violation';
    violation_count: number;
    processed_count: number;
    unprocessed_count: number;
    unknown_status_count: number;
    violations: LookupViolation[];
    checked_at: string;
};

type LookupResponse = {
    success: boolean;
    cached?: boolean;
    status?: string;
    message?: string;
    data?: unknown;
};

type TurnstileApi = {
    render: (
        container: HTMLElement,
        options: {
            sitekey: string;
            action: string;
            theme: 'light';
            size: 'flexible';
            language: 'vi';
            'response-field': boolean;
            callback: (token: string) => void;
            'error-callback': () => void;
            'expired-callback': () => void;
            'timeout-callback': () => void;
        },
    ) => string;
    reset: (widgetId: string) => void;
};

declare global {
    interface Window {
        turnstile?: TurnstileApi;
    }
}

const turnstileWidgetIds = new WeakMap<HTMLFormElement, string>();
let turnstileLoader: Promise<TurnstileApi> | null = null;

const setTurnstileError = (form: HTMLFormElement, message = ''): void => {
    const error = form.querySelector<HTMLElement>('[data-turnstile-error]');

    if (!error) {
        return;
    }

    error.textContent = message;
    error.classList.toggle('hidden', message === '');
};

const loadTurnstile = (): Promise<TurnstileApi> => {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    if (turnstileLoader) {
        return turnstileLoader;
    }

    turnstileLoader = new Promise<TurnstileApi>((resolve, reject) => {
        const existing = document.querySelector<HTMLScriptElement>('[data-turnstile-script]');
        const script = existing ?? document.createElement('script');
        const timeoutId = window.setTimeout(() => reject(new Error('Turnstile load timeout')), 10_000);
        const resolveWhenReady = (): void => {
            window.clearTimeout(timeoutId);

            if (window.turnstile) {
                resolve(window.turnstile);
            } else {
                reject(new Error('Turnstile unavailable'));
            }
        };
        const rejectLoad = (): void => {
            window.clearTimeout(timeoutId);
            reject(new Error('Turnstile failed to load'));
        };

        script.addEventListener('load', resolveWhenReady, { once: true });
        script.addEventListener('error', rejectLoad, { once: true });

        if (!existing) {
            script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
            script.async = true;
            script.defer = true;
            script.dataset.turnstileScript = '';
            document.head.append(script);
        }
    });

    return turnstileLoader;
};

const initializeTurnstileWidgets = async (): Promise<void> => {
    const containers = [...document.querySelectorAll<HTMLElement>('[data-turnstile-widget]')];

    if (containers.length === 0) {
        return;
    }

    try {
        const turnstile = await loadTurnstile();

        containers.forEach((container) => {
            const form = container.closest<HTMLFormElement>('[data-lookup-form]');
            const tokenInput = form?.elements.namedItem('cf-turnstile-response');
            const siteKey = container.dataset.siteKey ?? '';

            if (!form || !(tokenInput instanceof HTMLInputElement) || siteKey === '' || turnstileWidgetIds.has(form)) {
                return;
            }

            const clearToken = (message: string): void => {
                tokenInput.value = '';
                setTurnstileError(form, message);
            };
            const widgetId = turnstile.render(container, {
                sitekey: siteKey,
                action: container.dataset.action ?? 'traffic_fine_lookup',
                theme: 'light',
                size: 'flexible',
                language: 'vi',
                'response-field': false,
                callback: (token) => {
                    tokenInput.value = token;
                    setTurnstileError(form);
                },
                'error-callback': () => clearToken('Không thể xác minh bảo mật. Vui lòng thử lại.'),
                'expired-callback': () => clearToken('Xác minh đã hết hạn. Vui lòng xác minh lại.'),
                'timeout-callback': () => clearToken('Xác minh đã hết thời gian. Vui lòng thử lại.'),
            });
            turnstileWidgetIds.set(form, widgetId);
        });
    } catch {
        document.querySelectorAll<HTMLFormElement>('[data-lookup-form][data-turnstile-required="true"]').forEach((form) => {
            setTurnstileError(form, 'Không thể tải xác minh bảo mật. Vui lòng tải lại trang.');
        });
    }
};

const resetTurnstile = (form: HTMLFormElement): void => {
    const tokenInput = form.elements.namedItem('cf-turnstile-response');
    const widgetId = turnstileWidgetIds.get(form);

    if (tokenInput instanceof HTMLInputElement) {
        tokenInput.value = '';
    }

    if (widgetId && window.turnstile) {
        window.turnstile.reset(widgetId);
    }
};

const element = <K extends keyof HTMLElementTagNameMap>(tag: K, className = '', text = ''): HTMLElementTagNameMap[K] => {
    const node = document.createElement(tag);
    node.className = className;
    node.textContent = text;

    return node;
};

const svgIcon = (pathData: string, wrapperClass: string, svgClass = 'h-5 w-5'): HTMLSpanElement => {
    const wrapper = element('span', wrapperClass);
    wrapper.setAttribute('aria-hidden', 'true');
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.8');
    svg.setAttribute('class', svgClass);
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', pathData);
    svg.append(path);
    wrapper.append(svg);

    return wrapper;
};

const isRecord = (value: unknown): value is Record<string, unknown> => typeof value === 'object' && value !== null;
const isNullableString = (value: unknown): value is string | null => typeof value === 'string' || value === null;

const isLookupViolation = (value: unknown): value is LookupViolation => {
    if (!isRecord(value)) {
        return false;
    }

    return (
        ['plate_color', 'time', 'location', 'behavior', 'status', 'agency', 'resolution_agency', 'resolution_address', 'resolution_phone'].every(
            (key) => isNullableString(value[key]),
        ) &&
        (value.resolution_status === 'processed' || value.resolution_status === 'unprocessed' || value.resolution_status === 'unknown')
    );
};

const isLookupData = (value: unknown): value is LookupData => {
    if (!isRecord(value)) {
        return false;
    }

    const processedCount = value.processed_count;
    const unprocessedCount = value.unprocessed_count;
    const unknownStatusCount = value.unknown_status_count;

    return (
        typeof value.plate === 'string' &&
        typeof value.display_plate === 'string' &&
        typeof value.vehicle_type === 'string' &&
        (value.status === 'success' || value.status === 'no_violation') &&
        typeof value.violation_count === 'number' &&
        Number.isInteger(value.violation_count) &&
        value.violation_count >= 0 &&
        typeof processedCount === 'number' &&
        Number.isInteger(processedCount) &&
        processedCount >= 0 &&
        typeof unprocessedCount === 'number' &&
        Number.isInteger(unprocessedCount) &&
        unprocessedCount >= 0 &&
        typeof unknownStatusCount === 'number' &&
        Number.isInteger(unknownStatusCount) &&
        unknownStatusCount >= 0 &&
        processedCount + unprocessedCount + unknownStatusCount === value.violation_count &&
        typeof value.checked_at === 'string' &&
        Array.isArray(value.violations) &&
        value.violations.every(isLookupViolation)
    );
};

const formatDateTime = (value: string): string => {
    const normalizedValue = value.includes('T') ? value : value.replace(' ', 'T');
    const date = new Date(normalizedValue);

    if (Number.isNaN(date.getTime())) {
        return value || 'Chưa có dữ liệu';
    }

    return new Intl.DateTimeFormat('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    })
        .format(date)
        .replace(',', '');
};

const setResultShell = (container: HTMLElement): void => {
    container.className =
        'overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm outline-none focus:ring-2 focus:ring-sky-600 focus:ring-offset-2';
};

let resultAdvertisement = document.querySelector<HTMLElement>('[data-lookup-result-ad]');

if (resultAdvertisement && !resultAdvertisement.querySelector('[data-ad-slot]')) {
    resultAdvertisement.remove();
    resultAdvertisement = null;
}

const detachResultAdvertisement = (): void => {
    resultAdvertisement?.remove();
};

const attachResultAdvertisement = (target: HTMLElement): void => {
    if (!resultAdvertisement) {
        return;
    }

    resultAdvertisement.className = 'border-y border-slate-200 bg-slate-50 p-1';
    target.append(resultAdvertisement);
};

const restoreResultAdvertisement = (container: HTMLElement): void => {
    if (!resultAdvertisement) {
        return;
    }

    resultAdvertisement.className = 'mt-3';
    container.insertAdjacentElement('afterend', resultAdvertisement);
};

const renderMessage = (container: HTMLElement, title: string, message: string, tone: 'error' | 'warning' = 'warning'): void => {
    setResultShell(container);
    const wrapper = element(
        'div',
        tone === 'error' ? 'border-l-4 border-red-500 bg-red-50/70 p-4 sm:p-6' : 'border-l-4 border-amber-500 bg-amber-50/70 p-4 sm:p-6',
    );
    wrapper.setAttribute('role', 'alert');
    const layout = element('div', 'flex gap-3 sm:gap-4');
    const copy = element('div', 'min-w-0');
    copy.append(
        element('p', tone === 'error' ? 'text-base font-black text-red-950 sm:text-lg' : 'text-base font-black text-amber-950 sm:text-lg', title),
        element('p', tone === 'error' ? 'mt-1 text-sm leading-6 text-red-900' : 'mt-1 text-sm leading-6 text-amber-900', message),
    );
    layout.append(
        svgIcon(
            'M12 3 3 20h18L12 3ZM12 9v5m0 3h.01',
            tone === 'error'
                ? 'flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-800 sm:h-11 sm:w-11'
                : 'flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800 sm:h-11 sm:w-11',
            'h-6 w-6',
        ),
        copy,
    );
    wrapper.append(layout);
    container.replaceChildren(wrapper);
    restoreResultAdvertisement(container);
};

const metricItem = (label: string, value: number, tone: 'total' | 'danger' | 'success' | 'neutral'): HTMLDivElement => {
    const toneClass = {
        total: 'text-slate-600',
        danger: 'rounded-md bg-red-500 px-2.5 py-1.5 text-white shadow-sm',
        success: 'rounded-md bg-emerald-500 px-2.5 py-1.5 text-white shadow-sm',
        neutral: 'rounded-md bg-slate-200 px-2.5 py-1.5 text-slate-700',
    }[tone];
    const item = element('div', `inline-flex items-center gap-1 ${toneClass}`);
    item.dataset.resultCountPill = '';
    item.append(
        element('dt', '', label),
        element(
            'dd',
            tone === 'total' ? 'text-base font-black tabular-nums text-slate-800' : 'order-first font-black tabular-nums',
            tone === 'total' ? `${value} Lỗi` : String(value),
        ),
    );

    return item;
};

const detailItem = (label: string, value: string): HTMLDivElement => {
    const item = element('div', 'grid grid-cols-[92px_minmax(0,1fr)] gap-3 border-t border-slate-100 px-3 py-1.5 sm:grid-cols-[140px_minmax(0,1fr)]');
    item.append(
        element('dt', 'font-semibold text-slate-500', label),
        element('dd', 'break-words text-right font-semibold text-slate-700 [overflow-wrap:anywhere]', value),
    );

    return item;
};

const violationMetaItem = (label: string, value: string, iconPath: string): HTMLDivElement => {
    const item = element('div', 'flex items-start gap-2');
    item.append(svgIcon(iconPath, 'mt-0.5 shrink-0 text-indigo-500', 'h-4 w-4'));
    const copy = element('div', 'min-w-0 sm:flex sm:gap-2');
    copy.append(element('dt', 'font-bold text-slate-700', `${label}:`), element('dd', 'break-words text-slate-700 [overflow-wrap:anywhere]', value));
    item.append(copy);

    return item;
};

const violationCard = (violation: LookupViolation, index: number, displayPlate: string, vehicleLabel: string, penaltiesUrl: string): HTMLElement => {
    const card = element('article', 'overflow-hidden rounded-lg border border-slate-300 bg-white');
    card.dataset.violationCard = '';
    const header = element('header', 'flex items-center justify-between gap-2 border-b border-slate-200 px-3 py-2');
    header.dataset.violationHeader = '';
    const statusTone = {
        processed: 'bg-emerald-100 text-emerald-700',
        unprocessed: 'bg-red-100 text-red-700',
        unknown: 'bg-slate-200 text-slate-700',
    }[violation.resolution_status];
    const identity = element('div', 'flex min-w-0 items-center gap-2');
    identity.append(
        element('span', 'flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-red-500 text-xs font-black text-white', String(index + 1)),
        element('h4', 'truncate text-base font-black text-slate-800', displayPlate),
    );
    header.append(
        identity,
        element(
            'span',
            `inline-flex max-w-[55%] rounded-md px-2 py-1 text-right text-xs font-bold leading-4 ${statusTone}`,
            violation.status || 'Chưa rõ trạng thái',
        ),
    );

    const meta = element('dl', 'grid gap-2 px-3 py-2 text-sm');
    meta.dataset.violationMeta = '';
    meta.append(
        violationMetaItem(
            'Thời gian',
            violation.time ? formatDateTime(violation.time) : 'Chưa có dữ liệu',
            'M12 3a9 9 0 1 0 9 9 9 9 0 0 0-9-9Zm0 4v5l3 2',
        ),
        violationMetaItem(
            'Địa điểm',
            violation.location || 'Chưa có dữ liệu',
            'M12 21s7-6.2 7-12A7 7 0 1 0 5 9c0 5.8 7 12 7 12Zm0-9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5Z',
        ),
    );

    const behavior = element('div', 'mx-3 rounded-lg border border-red-200 bg-red-50 p-2.5');
    behavior.dataset.violationBehavior = '';
    const behaviorLabel = element('p', 'flex items-center gap-1.5 text-xs font-black uppercase tracking-[0.08em] text-red-700', 'Nội dung vi phạm');
    behaviorLabel.prepend(svgIcon('M12 3 3 20h18L12 3Zm0 6v5m0 3h.01', 'shrink-0 text-red-600', 'h-4 w-4'));
    const penaltyLink = element(
        'a',
        'site-focus mt-3 inline-flex min-h-11 touch-manipulation items-center gap-1.5 rounded-md bg-red-500 px-3 text-xs font-black text-white transition-colors hover:bg-red-600',
        'Xem mức phạt',
    );
    penaltyLink.href = penaltiesUrl;
    behavior.append(
        behaviorLabel,
        element(
            'p',
            'mt-2 break-words text-sm font-semibold leading-5 text-slate-700 [overflow-wrap:anywhere]',
            violation.behavior || 'Vi phạm giao thông',
        ),
        penaltyLink,
    );

    const details = element('dl', 'mt-3 border-t border-slate-100 text-sm');
    details.dataset.violationDetails = '';
    if (violation.plate_color) {
        details.append(detailItem('Màu biển', violation.plate_color));
    }
    details.append(detailItem('Loại xe', vehicleLabel), detailItem('Đơn vị phát hiện', violation.agency || 'Chưa có dữ liệu'));
    if (violation.resolution_agency) {
        details.append(detailItem('Nơi giải quyết', violation.resolution_agency));
    }
    if (violation.resolution_address) {
        details.append(detailItem('Địa chỉ', violation.resolution_address));
    }
    if (violation.resolution_phone) {
        details.append(detailItem('Điện thoại', violation.resolution_phone));
    }
    card.append(header, meta, behavior, details);

    return card;
};

const renderResult = (container: HTMLElement, data: LookupData, resultUrlTemplate: string, penaltiesUrl: string): void => {
    detachResultAdvertisement();
    setResultShell(container);
    const hasViolations = data.violation_count > 0;
    const vehicleLabels: Record<string, string> = {
        car: 'Ô tô',
        motorbike: 'Xe máy',
        electric_motorbike: 'Xe máy điện',
    };
    const vehicleLabel = vehicleLabels[data.vehicle_type] ?? data.vehicle_type;

    const header = element('div', 'px-3 py-3 text-center sm:px-4 sm:py-4');
    header.dataset.resultVisual = '';
    header.dataset.resultHeader = '';
    header.dataset.resultToolbar = '';
    header.dataset.resultTone = hasViolations ? 'violation' : 'clear';
    header.append(element('h2', 'sr-only', `Kết quả tra cứu biển số ${data.display_plate}`));

    if (hasViolations && data.violations.length > 0) {
        const resultAction = element(
            'a',
            'site-focus inline-flex min-h-11 touch-manipulation items-center justify-center gap-2 rounded-lg bg-red-500 px-5 text-sm font-black text-white shadow-[0_8px_20px_-10px_rgba(239,68,68,0.8)] transition-colors hover:bg-red-600',
            'Tra cứu vi phạm →',
        );
        resultAction.href = '#danh-sach-loi';
        header.append(resultAction);
    } else {
        header.append(
            element(
                'span',
                `inline-flex min-h-10 items-center gap-2 rounded-lg px-4 text-sm font-black ${hasViolations ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`,
                hasViolations ? `Có ${data.violation_count} vi phạm` : 'Chưa ghi nhận vi phạm',
            ),
        );
    }

    const updatedRow = element('div', 'mt-3 flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-sm text-slate-500');
    const updatedAt = element('span', 'inline-flex items-center gap-1.5');
    updatedAt.append(
        svgIcon('M12 3a9 9 0 1 0 9 9 9 9 0 0 0-9-9Zm0 4v5l3 2', 'shrink-0 text-slate-400', 'h-4 w-4'),
        element('span', '', `Cập nhật: ${formatDateTime(data.checked_at)}`),
    );
    const lookupAgain = element(
        'a',
        'site-focus inline-flex min-h-11 touch-manipulation items-center font-bold text-indigo-600 underline decoration-indigo-200 underline-offset-4 hover:text-indigo-800',
        'Tra cứu lại',
    );
    lookupAgain.href = '#tra-cuu';
    const permalink = element(
        'a',
        'site-focus inline-flex min-h-11 items-center font-bold text-slate-600 underline decoration-slate-200 underline-offset-4 hover:text-slate-900',
        'Mở trang đầy đủ',
    );
    permalink.href = `${resultUrlTemplate.replace('__PLATE__', encodeURIComponent(data.plate))}?vehicle_type=${encodeURIComponent(data.vehicle_type)}`;
    updatedRow.append(updatedAt, lookupAgain, permalink);
    header.append(updatedRow, element('p', 'mt-1 text-xs font-semibold text-slate-500', `${data.display_plate} · ${vehicleLabel}`));

    const metrics = element('dl', 'mt-3 flex flex-wrap items-center justify-center gap-2 text-xs font-bold');
    metrics.dataset.resultMetrics = '';
    metrics.append(
        metricItem('Tổng:', data.violation_count, 'total'),
        metricItem('Chưa xử phạt', data.unprocessed_count, 'danger'),
        metricItem('Đã xử phạt', data.processed_count, 'success'),
    );
    if (data.unknown_status_count > 0) {
        metrics.append(metricItem('Chưa rõ', data.unknown_status_count, 'neutral'));
    }
    header.append(metrics);

    const advertisementTarget = element('div');
    advertisementTarget.dataset.resultAdTarget = '';
    advertisementTarget.setAttribute('aria-live', 'off');

    let details: HTMLElement;
    if (data.violations.length === 0) {
        details = element('div', 'border-t border-slate-200 bg-slate-50 px-3 py-4 text-center');
        details.dataset.resultEmpty = '';
        details.append(
            svgIcon(
                'M12 3 19 6v5c0 4.5-3 7.8-7 10-4-2.2-7-5.5-7-10V6l7-3Zm-3 9 2 2 4-5',
                'mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700',
                'h-6 w-6',
            ),
            element('h3', 'mt-2 text-base font-black text-slate-950', 'Chưa có thông tin vi phạm'),
            element('p', 'mt-1 text-sm leading-5 text-slate-600', 'Kết quả phản ánh dữ liệu hiện có tại thời điểm kiểm tra.'),
        );
    } else {
        details = element('section', 'scroll-mt-24 border-t border-slate-200 bg-slate-50 p-2.5 sm:p-3');
        details.id = 'danh-sach-loi';
        details.setAttribute('aria-labelledby', 'violation-list-title');
        const detailsHeading = element('div', 'flex items-center justify-between gap-3 pb-2');
        const heading = element('h3', 'text-base font-black text-slate-900', 'Danh sách vi phạm');
        heading.id = 'violation-list-title';
        detailsHeading.append(heading, element('span', 'text-xs font-semibold text-slate-500', `${data.violations.length} chi tiết`));
        const list = element('div', 'grid gap-3');
        list.dataset.violationList = '';
        data.violations.forEach((violation, index) => list.append(violationCard(violation, index, data.display_plate, vehicleLabel, penaltiesUrl)));
        details.append(detailsHeading, list);
    }

    const disclaimer = element('div', 'flex gap-2.5 border-t border-slate-200 bg-white px-3 py-2.5 text-[11px] leading-5 text-slate-500 sm:px-4');
    disclaimer.append(
        svgIcon('M10 2.5 16 5v4.5c0 3.7-2.5 6.5-6 8-3.5-1.5-6-4.3-6-8V5l6-2.5ZM10 7v3m0 3h.01', 'mt-0.5 shrink-0 text-sky-600', 'h-4 w-4'),
        element('p', '', 'Kết quả không thay thế xác nhận từ cơ quan có thẩm quyền.'),
    );

    container.replaceChildren(header, advertisementTarget, details, disclaimer);
    attachResultAdvertisement(advertisementTarget);
};

const serverAdvertisementTarget = document.querySelector<HTMLElement>('[data-result-ad-target]');
if (serverAdvertisementTarget) {
    detachResultAdvertisement();
    attachResultAdvertisement(serverAdvertisementTarget);
}

void initializeTurnstileWidgets();

document.querySelectorAll<HTMLFormElement>('[data-lookup-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const result = document.querySelector<HTMLElement>('[data-lookup-result]');
        const button = form.querySelector<HTMLButtonElement>('button[type="submit"]');
        const submitLabel = form.querySelector<HTMLElement>('[data-submit-label]');
        const endpoint = form.dataset.endpoint ?? '/api/lookup';
        const resultUrl = form.dataset.resultUrl ?? '/tra-cuu/__PLATE__';
        const penaltiesUrl = form.dataset.penaltiesUrl ?? '/muc-phat';
        const plateInput = form.elements.namedItem('plate') as HTMLInputElement | null;
        const vehicleInput = form.elements.namedItem('vehicle_type');
        const fieldError = form.querySelector<HTMLElement>('[data-lookup-error]');
        const turnstileRequired = form.dataset.turnstileRequired === 'true';
        const turnstileTokenInput = form.elements.namedItem('cf-turnstile-response');
        const turnstileToken = turnstileTokenInput instanceof HTMLInputElement ? turnstileTokenInput.value.trim() : '';

        if (
            !result ||
            !button ||
            !submitLabel ||
            !plateInput ||
            !(vehicleInput instanceof RadioNodeList || vehicleInput instanceof HTMLSelectElement) ||
            button.disabled
        ) {
            return;
        }

        if (turnstileRequired && turnstileToken === '') {
            setTurnstileError(form, 'Vui lòng hoàn tất xác minh bảo mật trước khi tra cứu.');
            form.querySelector<HTMLElement>('[data-turnstile-widget]')?.focus();
            return;
        }

        button.disabled = true;
        submitLabel.textContent = 'ĐANG TRA CỨU';
        plateInput.removeAttribute('aria-invalid');
        fieldError?.classList.add('hidden');
        if (fieldError) {
            fieldError.textContent = '';
        }
        setTurnstileError(form);
        result.setAttribute('aria-busy', 'true');
        detachResultAdvertisement();
        setResultShell(result);
        const loading = element('div', 'm-3 rounded-xl border border-slate-200 bg-white p-4 text-slate-950 sm:m-7 sm:p-6');
        loading.setAttribute('role', 'status');
        loading.append(
            element('p', 'font-extrabold', 'Đang kiểm tra biển số...'),
            element('p', 'mt-1 text-sm text-slate-500', 'Hệ thống đang tổng hợp dữ liệu mới nhất.'),
            element('div', 'mt-4 h-1.5 motion-safe:animate-pulse rounded-full bg-cyan-500/70'),
        );
        result.replaceChildren(loading);
        result.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });

        try {
            const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                body: JSON.stringify({
                    plate: plateInput.value,
                    vehicle_type: vehicleInput.value,
                    ...(turnstileRequired ? { 'cf-turnstile-response': turnstileToken } : {}),
                }),
            });
            const payload = (await response.json()) as LookupResponse;

            if (!response.ok || !payload.success || !isLookupData(payload.data)) {
                const captchaTitle =
                    payload.status === 'captcha_unavailable'
                        ? 'Xác minh đang gián đoạn'
                        : payload.status === 'captcha_required' || payload.status === 'captcha_failed'
                          ? 'Cần xác minh bảo mật'
                          : null;
                const title =
                    payload.status === 'rate_limited'
                        ? 'Bạn đã tra cứu quá nhanh'
                        : payload.status === 'invalid_plate'
                          ? 'Biển số chưa hợp lệ'
                          : 'Chưa thể trả kết quả';
                renderMessage(
                    result,
                    captchaTitle ?? title,
                    payload.message ?? 'Vui lòng kiểm tra thông tin và thử lại.',
                    payload.status === 'invalid_plate' ? 'error' : 'warning',
                );
                if (payload.status === 'invalid_plate') {
                    plateInput.setAttribute('aria-invalid', 'true');
                    if (fieldError) {
                        fieldError.textContent = payload.message ?? 'Vui lòng kiểm tra lại định dạng biển số.';
                        fieldError.classList.remove('hidden');
                    }
                    plateInput.focus();
                } else if (payload.status?.startsWith('captcha_')) {
                    setTurnstileError(form, payload.message ?? 'Vui lòng xác minh lại.');
                    form.querySelector<HTMLElement>('[data-turnstile-widget]')?.focus();
                } else {
                    result.focus({ preventScroll: true });
                }
                return;
            }

            renderResult(result, payload.data, resultUrl, penaltiesUrl);
            result.focus({ preventScroll: true });

            try {
                localStorage.setItem(
                    'recent_traffic_fine_lookup',
                    JSON.stringify({ plate: payload.data.plate, vehicle_type: payload.data.vehicle_type, checked_at: payload.data.checked_at }),
                );
            } catch {
                // Local storage may be unavailable in privacy mode; lookup still succeeds.
            }
        } catch {
            renderMessage(result, 'Không thể kết nối', 'Vui lòng kiểm tra mạng và thử lại sau ít phút.', 'error');
            result.focus({ preventScroll: true });
        } finally {
            if (turnstileRequired) {
                resetTurnstile(form);
            }
            button.disabled = false;
            submitLabel.textContent = 'TRA CỨU NGAY';
            result.setAttribute('aria-busy', 'false');
        }
    });
});

export {};
