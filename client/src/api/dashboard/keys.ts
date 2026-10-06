/**
 * Query keys for the dashboard's own reads: the header counts. They live outside
 * `articleKeys.lists()` on purpose, so the processing-status cache writes (which patch every
 * article list page) never touch a one-row count query. Anything that changes what the owner
 * has (a delete, a create) invalidates `dashboardKeys.all`.
 */
export const dashboardKeys = {
	all: ['dashboard'] as const,
	counts: (ownerUuid: string) => ['dashboard', 'counts', ownerUuid] as const,
} as const;
