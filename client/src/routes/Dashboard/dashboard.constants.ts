export const RESOURCE_TYPES = {
	ARTICLES: 'ARTICLES',
	LISTS: 'LISTS',
} as const;

export type ResourceType = (typeof RESOURCE_TYPES)[keyof typeof RESOURCE_TYPES];

export const DASHBOARD_TYPES = {
	ADMIN: 'ADMIN',
	COMMON_USER: 'COMMON_USER',
} as const;

export type DashboardType = (typeof DASHBOARD_TYPES)[keyof typeof DASHBOARD_TYPES];
