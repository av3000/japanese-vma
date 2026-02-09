export interface PaginationMeta {
	page: number;
	per_page: number;
	total: number;
	last_page: number;
	has_more: boolean;
}

export interface PaginatedResponse<T> {
	items: T[];
	pagination: PaginationMeta;
}
