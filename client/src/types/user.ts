export interface User {
	id: number;
	uuid: string;
	name: string;
	email: string;
	roles: string[];
	isAdmin: boolean;
	created_at?: string;
}
