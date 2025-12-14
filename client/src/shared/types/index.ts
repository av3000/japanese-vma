export const HttpMethod = {
	GET: 'get',
	POST: 'post',
	PUT: 'put',
	PATCH: 'patch',
	DELETE: 'delete',
} as const;

export type HttpMethod = (typeof HttpMethod)[keyof typeof HttpMethod];
