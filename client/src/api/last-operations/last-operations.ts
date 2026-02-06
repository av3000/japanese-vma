export const LastOperationStatus = {
	Pending: 'pending',
	Processing: 'processing',
	Completed: 'completed',
	Failed: 'failed',
} as const;

export type LastOperationStatus = (typeof LastOperationStatus)[keyof typeof LastOperationStatus];

export interface LastOperationEvent {
	id: number;
	type: string;
	status: LastOperationStatus;
	metadata: Record<string, any>;
}
