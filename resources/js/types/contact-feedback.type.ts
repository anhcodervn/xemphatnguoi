export type ContactFeedbackPayload = {
    name?: string | null;
    email?: string | null;
    phone?: string | null;
    subject: string;
    content: string;
};

export type ContactInfoResponse = {
    name: string | null;
    email: string | null;
    phone: string | null;
};
