export interface MediaSet {
	readonly size180: MediaInfo;
	readonly size320?: MediaInfo;
	readonly size480?: MediaInfo;
	readonly size720?: MediaInfo;
	readonly size960?: MediaInfo;
	readonly size1280?: MediaInfo;
	readonly size1920?: MediaInfo;
}

export interface MediaInfo {
	readonly url: AbsoluteUrl;
	readonly height?: number | null;
	readonly width?: number | null;
}

export type AbsoluteUrl = string;
