import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

type StatusNoteProps = {
    children: string;
};

export default function StatusNote({ children }: StatusNoteProps) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span
                    tabIndex={0}
                    className="mt-1 block max-w-64 whitespace-normal break-words text-xs leading-5 text-muted-foreground outline-none"
                >
                    {children}
                </span>
            </TooltipTrigger>
            <TooltipContent className="max-w-80 whitespace-normal break-words leading-5">
                {children}
            </TooltipContent>
        </Tooltip>
    );
}
