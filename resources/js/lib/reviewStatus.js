export const REVIEW_STATUS_LABELS = {
    pass: 'Pass',
    needs_work: 'Needs work',
    defunct: 'Defunct',
    do_not_review: 'Do not review',
    due_today: 'Due today',
    overdue: 'Overdue',
    scheduled: 'Scheduled',
};

export const REVIEW_STATUS_CLASSES = {
    pass: 'bg-emerald-900/60 text-emerald-200',
    needs_work: 'bg-orange-900/60 text-orange-200',
    defunct: 'bg-red-900/60 text-red-200',
    do_not_review: 'bg-slate-600 text-slate-300',
    due_today: 'bg-amber-900/60 text-amber-200',
    overdue: 'bg-red-900/60 text-red-200',
    scheduled: 'bg-slate-700 text-slate-200',
};

export function reviewStatusLabel(status) {
    return REVIEW_STATUS_LABELS[status] ?? status;
}

export function reviewStatusClass(status) {
    return REVIEW_STATUS_CLASSES[status] ?? 'bg-slate-700 text-slate-200';
}

export function reviewBadgeLabel(review) {
    if (!review) {
        return '—';
    }

    if (review.reviewOutcome) {
        return reviewStatusLabel(review.reviewOutcome);
    }

    if (review.status === 'do_not_review') {
        return 'Skip';
    }

    if (review.reviewDate) {
        return new Date(`${review.reviewDate}T12:00:00`).toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
        });
    }

    return '—';
}
