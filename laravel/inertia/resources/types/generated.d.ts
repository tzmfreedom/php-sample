declare namespace App.Data {
    export type UsersPageData = {
        data: {
            users: { id: number; name: string; email: string; email_verified_at: string | null; created_at: string; updated_at: string }[];
            pagination: {
                current_page: number;
                last_page: number;
                per_page: number;
                total: number;
                from: number | null;
                to: number | null;
                prev_page_url: string | null;
                next_page_url: string | null;
            };
            filters: { search: string | null };
        };
    };
}
declare namespace App.Models {
    export type DemoData = {
        message: string;
        success: boolean;
    };
}
