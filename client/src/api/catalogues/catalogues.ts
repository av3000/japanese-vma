import type { CatalogueDetailResourceCatalogue } from '@/api/generated/model/catalogueDetailResourceCatalogue';
import type { CatalogueIndexParams } from '@/api/generated/model/catalogueIndexParams';
import type { CatalogueResource } from '@/api/generated/model/catalogueResource';
export type Catalogue = CatalogueResource;
export type CatalogueDetails = CatalogueDetailResourceCatalogue;
export type FetchCataloguesFilters = Omit<CatalogueIndexParams, 'page'>;
