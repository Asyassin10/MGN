import { router } from '@inertiajs/react';
import DateRangePicker from '@/Components/DateRangePicker';

export default function CreatedAtFilter({ routeName, routeParams = {}, filters = {} }) {
    const apply = (from, to) => {
        const query = { ...filters };
        delete query.created_from;
        delete query.created_to;
        if (from) query.created_from = from;
        if (to) query.created_to = to;
        router.get(route(routeName, routeParams), query, { preserveState: true, replace: true });
    };

    return <DateRangePicker from={filters.created_from || ''} to={filters.created_to || ''} onChange={({ from, to }) => apply(from, to)} label="Date" showToday />;
}
