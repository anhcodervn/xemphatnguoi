const pad = (value: number): string => String(value).padStart(2, "0");

export const formatTime = (
    time: string | number | Date | null | undefined,
    output = "H:i:s d/m/Y",
): string => {
    if (!time) {
        return "";
    }

    const date = time instanceof Date ? time : new Date(time);

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    const replacements: Record<string, string> = {
        Y: String(date.getFullYear()),
        m: pad(date.getMonth() + 1),
        d: pad(date.getDate()),
        H: pad(date.getHours()),
        i: pad(date.getMinutes()),
        s: pad(date.getSeconds()),
    };

    return output.replace(/Y|m|d|H|i|s/g, (token) => replacements[token] ?? token);
};
