export type HttpValidationProblemDetails = {
	errors: Record<string, string[]>;
	title?: string;
	status?: number;
	detail?: string;
	instance?: string;
	timestamp?: string;
};

export function isHttpValidationProblemDetails(obj: unknown): obj is HttpValidationProblemDetails {
	return (obj as HttpValidationProblemDetails).errors !== undefined;
}
