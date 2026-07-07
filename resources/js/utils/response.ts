import Swal from 'sweetalert2';

type ErrorPayload = {
    message?: string;
    errors?: Record<string, string[]>;
};

type ErrorLike = {
    message?: string;
    response?: {
        status?: number;
        data?: ErrorPayload;
    };
};

export const handleSuccessResponse = (response: any, message?: string) => {
    const res = response?.data ?? {};

    if (res.status) {
        Swal.fire('Thành công', message || res.message, 'success');
        return;
    }

    Swal.fire({
        icon: 'error',
        title: 'Có lỗi xảy ra',
        text: message || res.message || 'Có lỗi xảy ra, vui lòng thử lại hoặc báo dev kiểm tra.',
    });
};

export const handleErrorResponse = (err: ErrorLike) => {
    const status = err?.response?.status;
    const data = err?.response?.data;

    if (!status) {
        Swal.fire({
            icon: 'warning',
            title: 'Chưa thể gửi dữ liệu',
            text: err?.message || 'Dữ liệu chưa hợp lệ, vui lòng kiểm tra lại.',
        });
        return;
    }

    switch (status) {
        case 400:
            Swal.fire({
                icon: 'warning',
                title: 'Lỗi xác thực dữ liệu',
                text: data?.message || 'Dữ liệu không hợp lệ',
            });
            break;

        case 401:
            Swal.fire({
                icon: 'warning',
                title: 'Chưa đăng nhập',
                text: 'Vui lòng đăng nhập lại',
            }).then(() => {
                window.location.href = '/login';
            });
            break;

        case 403:
            Swal.fire({
                icon: 'error',
                title: 'Forbidden',
                text: 'Bạn không có quyền',
            });
            break;

        case 404:
            Swal.fire({
                icon: 'error',
                title: 'Not Found',
                text: 'API không tồn tại',
            });
            break;

        case 419:
            Swal.fire({
                icon: 'warning',
                title: 'Phiên hết hạn',
                text: 'Vui lòng refresh lại trang',
            }).then(() => {
                location.reload();
            });
            break;

        case 422: {
            const errors = data?.errors ? Object.values(data.errors).flat().join('<br>') : '';

            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                html: errors || data?.message || 'Dữ liệu không hợp lệ',
            });
            break;
        }

        case 500:
            Swal.fire({
                icon: 'error',
                title: 'Server Error',
                text: 'Lỗi server rồi, báo admin ngay nhé.',
            });
            break;

        default:
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data?.message || err?.message || 'Có lỗi xảy ra',
            });
    }
};
