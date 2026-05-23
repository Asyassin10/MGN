import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function money(value) {
    return new Intl.NumberFormat('fr-MA', {
        style: 'currency',
        currency: 'MAD',
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
}

export function number(value) {
    return new Intl.NumberFormat('fr-MA', {
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}
