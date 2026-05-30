import Swal from "sweetalert2";

export const handleSuccessResponse = (response: any, message?: string) => {
    const res = response.data;

    if (res.status) {
        Swal.fire("Thành công", message || res.message, "success");
    } else {
        Swal.fire({
            icon: "error",
            title: "Có lỗi xảy ra",
            text: message || res.message || "Có lỗi xảy ra, vui lòng thử lại hoặc báo dev kiểm tra.",
        });
    }
};

export const handleErrorResponse = (err: any) => {
    const { status, data } = err.response;

    switch (status) {
        case 400:
            Swal.fire({
                icon: "warning",
                title: "Lỗi xác thực dữ liệu",
                text: data.message || "Dữ liệu không hợp lệ",
            });
            break;

        case 401:
            Swal.fire({
                icon: "warning",
                title: "Chưa đăng nhập",
                text: "Vui lòng đăng nhập lại",
            }).then(() => {
                window.location.href = "/login";
            });
            break;

        case 403:
            Swal.fire({
                icon: "error",
                title: "Forbidden",
                text: "Bạn không có quyền",
            });
            break;

        case 404:
            Swal.fire({
                icon: "error",
                title: "Not Found",
                text: "API không tồn tại",
            });
            break;

        case 419:
            Swal.fire({
                icon: "warning",
                title: "Phiên hết hạn",
                text: "Vui lòng refresh lại trang",
            }).then(() => {
                location.reload();
            });
            break;

        case 422: {
            let errors = "";

            if (data.errors) {
                errors = Object.values(data.errors).flat().join("<br>");
            }

            Swal.fire({
                icon: "warning",
                title: "Validation Error",
                html: errors || data.message,
            });
            break;
        }

        case 500:
            Swal.fire({
                icon: "error",
                title: "Server Error",
                text: "Lỗi server rồi, báo admin ngay nhé.",
            });
            break;

        default:
            Swal.fire({
                icon: "error",
                title: "Error",
                text: data.message || "Có lỗi xảy ra",
            });
    }
};
