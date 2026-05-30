export default (balance: number): string => {
    if (!Number.isFinite(balance)) return "0"
    const abs = Math.abs(balance)
    const formatted = new Intl.NumberFormat('vi-VN', {
        maximumFractionDigits: 0
    }).format(abs)

    return balance < 0 ? `(${formatted})` : formatted
}