import DOMPurify from 'dompurify';

export const sanitizeRichText = (value: string | null | undefined, fallback = ''): string => {
    const content = value?.trim() || fallback;

    return content ? DOMPurify.sanitize(content, { USE_PROFILES: { html: true } }) : '';
};

export const richTextToPlainText = (value: string | null | undefined, fallback = ''): string => {
    if (!value?.trim()) {
        return fallback;
    }

    if (typeof document === 'undefined') {
        return (
            value
                .replace(/<[^>]*>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim() || fallback
        );
    }

    const container = document.createElement('div');
    container.innerHTML = value;

    return (container.textContent ?? '').replace(/\s+/g, ' ').trim() || fallback;
};
