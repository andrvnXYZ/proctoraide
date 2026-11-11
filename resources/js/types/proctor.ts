export type ProctorExaminationStatus = 'scheduled' | 'ongoing' | 'completed';

export interface ProctorExamination {
    id: string;
    title: string;
    subject: string;
    section: string;
    scheduled_date: string;
    start_time: string;
    end_time: string;
    date_label: string;
    time_range: string;
    schedule: string;
    status: ProctorExaminationStatus;
    status_label: string;
    access_code: string;
    started_at: string | null;
    ended_at: string | null;
    enrolled_students?: number | null;
}

export interface ProctorAssignment {
    subject: string;
    section: string;
}

export interface ProctorStudent {
    id: string;
    student_id: string;
    student_name: string;
    student_id_number: string | null;
    subject: string | null;
    section: string | null;
    year_level: string | null;
}

export type AttendanceStatus = 'present' | 'absent' | 'late' | 'excused' | 'not_checked';

export interface AttendanceRow {
    enrollment_id: string;
    student_id: string;
    student_name: string;
    student_id_number: string | null;
    year_level: string | null;
    status: AttendanceStatus;
    status_label: string;
    recorded_at: string | null;
}

export interface AttendanceCounts {
    present: number;
    absent: number;
    late: number;
    excused: number;
    not_checked: number;
}

export interface AttendanceSummaryData {
    rows: AttendanceRow[];
    counts: AttendanceCounts;
    total: number;
    recorded: number;
}

export type IncidentStatus = 'pending' | 'reviewed' | 'dismissed';

export interface ProctorIncident {
    id: string;
    examination_id: string;
    examination_title: string | null;
    student_name: string | null;
    student_id_number: string | null;
    description: string;
    status: IncidentStatus;
    status_label: string;
    reported_at: string | null;
    reviewed_at: string | null;
}

export interface ExaminationWithStudents extends ProctorExamination {
    students: Array<{ id: string; name: string; student_id_number: string | null }>;
}

export interface ReportRow extends ProctorExamination {
    counts: AttendanceCounts;
    recorded: number;
    attendance_rate: number;
}

export interface ProctorFlash {
    success?: string | null;
    error?: string | null;
}
