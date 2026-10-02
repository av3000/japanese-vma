import { readWriteFailure, type WriteFailure } from '@/api/writeFailure';

export const GENERIC_ARTICLE_WRITE_ERROR = 'Something went wrong. Please try again.';

export type ArticleWriteFailure = WriteFailure;

export const readArticleWriteError = (error: unknown): ArticleWriteFailure =>
	readWriteFailure(error, GENERIC_ARTICLE_WRITE_ERROR);
