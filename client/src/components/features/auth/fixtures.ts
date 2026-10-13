/** What a rejected `login` or `register` call throws: an axios-shaped error with a response. */
const failure = (status: number, data: unknown) =>
	Object.assign(new Error(`Request failed with status code ${status}`), { response: { status, data } });

/** A 422 Problem Details body: field messages the form places under each field. */
export const validationFailure = (errors: Record<string, string[]>) =>
	failure(422, { title: 'The given data was invalid.', status: 422, errors });

/** A 401: the credentials were wrong. */
export const invalidCredentials = () => failure(401, { title: 'Unauthorized', status: 401 });
