import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

export const Dialog = DialogPrimitive.Root;
export const DialogTrigger = DialogPrimitive.Trigger;

export function DialogContent({ className, children, ...props }) {
    return (
        <DialogPrimitive.Portal>
            <DialogPrimitive.Overlay className="fixed inset-0 z-40 bg-black/20" />
            <DialogPrimitive.Content className={cn('fixed left-1/2 top-1/2 z-50 max-h-[calc(100vh-2rem)] w-[calc(100%-2rem)] max-w-xl -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-md border border-zinc-200 bg-white p-4', className)} {...props}>
                {children}
                <DialogPrimitive.Close className="absolute right-3 top-3 rounded-md p-1 text-zinc-500 hover:bg-zinc-100">
                    <X className="h-4 w-4" />
                </DialogPrimitive.Close>
            </DialogPrimitive.Content>
        </DialogPrimitive.Portal>
    );
}

export function DialogHeader({ className, ...props }) {
    return <div className={cn('mb-4', className)} {...props} />;
}

export function DialogTitle({ className, ...props }) {
    return <DialogPrimitive.Title className={cn('text-base font-semibold text-zinc-950', className)} {...props} />;
}
