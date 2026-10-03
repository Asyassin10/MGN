import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

export const Dialog = DialogPrimitive.Root;
export const DialogTrigger = DialogPrimitive.Trigger;

export function DialogContent({ className, children, ...props }) {
    return (
        <DialogPrimitive.Portal>
            <DialogPrimitive.Overlay className="fixed inset-0 z-40 bg-black/20" />
            <DialogPrimitive.Content className={cn('fixed left-1/2 top-1/2 z-50 max-h-[calc(100vh-2rem)] w-[calc(100%-2rem)] max-w-xl -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-md border-2 border-blue-400 bg-gradient-to-b from-sky-100 via-blue-100 to-blue-200 p-4 shadow-xl', className)} {...props}>
                {children}
                <DialogPrimitive.Close className="absolute right-3 top-3 rounded-md p-1 text-blue-800 hover:bg-blue-200">
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
    return <DialogPrimitive.Title className={cn('text-lg font-semibold text-blue-950', className)} {...props} />;
}
