import { FileSpreadsheet, FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

interface ExportButtonsProps {
    module: string;
}

export function ExportButtons({ module }: ExportButtonsProps) {
    return (
        <div className="flex items-center gap-2">
            <Tooltip>
                <TooltipTrigger asChild>
                    <Button variant="outline" size="sm" disabled aria-label={`Exporter ${module} en Excel`}>
                        <FileSpreadsheet className="mr-1.5 h-4 w-4 text-emerald-600" />
                        Excel
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Disponible en phase 3</TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger asChild>
                    <Button variant="outline" size="sm" disabled aria-label={`Exporter ${module} en PDF`}>
                        <FileText className="mr-1.5 h-4 w-4 text-red-500" />
                        PDF
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Disponible en phase 3</TooltipContent>
            </Tooltip>
        </div>
    );
}
