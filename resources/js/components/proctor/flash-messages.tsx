import { type SharedData } from '@/types';
import { type ProctorFlash } from '@/types/proctor';
import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle2 } from 'lucide-react';

export default function FlashMessages() {
    const { flash } = usePage<SharedData & { flash?: ProctorFlash }>().props;

    if (!flash?.success && !flash?.error) {
        return null;
    }

    return (
        <div className="space-y-3">
            {flash.success && (
                <div
                    role="status"
                    className="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
                >
                    <CheckCircle2 className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    <p>{flash.success}</p>
                </div>
            )}
            {flash.error && (
                <div role="alert" className="flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <AlertCircle className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    <p>{flash.error}</p>
                </div>
            )}
        </div>
    );
}
