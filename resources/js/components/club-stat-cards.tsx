import {
    IconAlertTriangle,
    IconCircleCheck,
    type Icon,
} from '@tabler/icons-react';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardAction,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

export type StatCard = {
    label: string;
    value: string;
    badge?: string;
    /** Amber when something needs attention, plain otherwise. */
    needsAttention?: boolean;
    headline: string;
    detail: string;
    icon?: Icon;
};

/**
 * The dashboard-01 card treatment, carrying club figures.
 *
 * The block's cards show a trend percentage; the club has no trend to show
 * yet, so the badge slot carries whatever qualifier the figure needs — the
 * period it belongs to, or a warning that it is unconfirmed.
 */
export function ClubStatCards({ cards }: { cards: StatCard[] }) {
    return (
        <div className="grid grid-cols-1 gap-4 px-4 *:data-[slot=card]:bg-gradient-to-t *:data-[slot=card]:from-primary/5 *:data-[slot=card]:to-card *:data-[slot=card]:shadow-xs lg:px-6 @xl/main:grid-cols-2 @5xl/main:grid-cols-4 dark:*:data-[slot=card]:bg-card">
            {cards.map((card) => {
                const TrailingIcon =
                    card.icon ??
                    (card.needsAttention ? IconAlertTriangle : IconCircleCheck);

                return (
                    <Card key={card.label} className="@container/card">
                        <CardHeader>
                            <CardDescription>{card.label}</CardDescription>
                            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                                {card.value}
                            </CardTitle>
                            {card.badge && (
                                <CardAction>
                                    <Badge
                                        variant={
                                            card.needsAttention
                                                ? 'destructive'
                                                : 'outline'
                                        }
                                    >
                                        {card.badge}
                                    </Badge>
                                </CardAction>
                            )}
                        </CardHeader>
                        <CardFooter className="flex-col items-start gap-1.5 text-sm">
                            <div className="line-clamp-1 flex gap-2 font-medium">
                                {card.headline}
                                <TrailingIcon className="size-4" />
                            </div>
                            <div className="text-muted-foreground">
                                {card.detail}
                            </div>
                        </CardFooter>
                    </Card>
                );
            })}
        </div>
    );
}
