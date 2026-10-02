import { readWriteFailure, type WriteFailure } from '@/api/writeFailure';

export const GENERIC_CATALOGUE_WRITE_ERROR = 'Something went wrong. Please try again.';

export type CatalogueWriteFailure = WriteFailure;

export const readCatalogueWriteError = (error: unknown): CatalogueWriteFailure =>
	readWriteFailure(error, GENERIC_CATALOGUE_WRITE_ERROR);
