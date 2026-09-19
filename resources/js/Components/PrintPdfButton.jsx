import { FileText } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { printPdf } from '@/lib/utils';

export default function PrintPdfButton({ url, label = 'Voir PDF', size, variant = 'outline' }) {
    return (
        <Button type="button" size={size} variant={variant} onClick={() => printPdf(url)}>
            <FileText className="h-4 w-4" />
            {label}
        </Button>
    );
}
