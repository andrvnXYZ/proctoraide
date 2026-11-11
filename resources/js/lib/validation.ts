export const PASSWORD_MIN_LENGTH = 10;

/**
 * Requires a local part, an "@" sign, a dotted domain and a 2-63 letter TLD.
 * Mirrors the backend ValidEmailAddress rule.
 */
const EMAIL_PATTERN = /^[A-Za-z0-9!#$%&'*+/=?^_`{|}~.-]+@(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,63}$/;

export function normalizeEmail(value: string): string {
    return value.trim().toLowerCase();
}

export function isValidEmail(value: string): boolean {
    return EMAIL_PATTERN.test(normalizeEmail(value));
}

export interface PasswordRequirement {
    label: string;
    met: (password: string) => boolean;
}

/** Mirrors the backend policy: min 10 characters, mixed case, at least one number. */
export const passwordRequirements: PasswordRequirement[] = [
    { label: `At least ${PASSWORD_MIN_LENGTH} characters`, met: (password) => password.length >= PASSWORD_MIN_LENGTH },
    { label: 'An uppercase letter (A-Z)', met: (password) => /[A-Z]/.test(password) },
    { label: 'A lowercase letter (a-z)', met: (password) => /[a-z]/.test(password) },
    { label: 'A number (0-9)', met: (password) => /[0-9]/.test(password) },
];

export function unmetPasswordRequirements(password: string): PasswordRequirement[] {
    return passwordRequirements.filter((requirement) => !requirement.met(password));
}

export function passwordIssuesMessage(password: string): string | undefined {
    const unmet = unmetPasswordRequirements(password);

    if (unmet.length === 0) {
        return undefined;
    }

    return `Password must include: ${unmet.map((requirement) => requirement.label.toLowerCase()).join(', ')}.`;
}
