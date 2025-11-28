export interface User {
	id: string;
	uuid: string;
	name: string;
	email: string;
	roles: string[];
	isAdmin: boolean;
	created_at?: string;
}
