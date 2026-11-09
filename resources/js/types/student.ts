export type ExaminationStatus = 'scheduled' | 'ongoing' | 'completed' | 'missed';

export interface Examination {
    id: string | number;
    title: string;
    course?: string | null;
    course_code?: string | null;
    schedule?: string | null;
    starts_at?: string | null;
    ends_at?: string | null;
    duration_minutes?: number | null;
    status: ExaminationStatus;
    score?: number | null;
    total_items?: number | null;
    join_url?: string | null;
}

export interface Announcement {
    id: string | number;
    title: string;
    body?: string | null;
    published_at?: string | null;
    is_unread?: boolean;
}
