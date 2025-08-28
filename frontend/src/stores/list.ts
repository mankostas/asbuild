export interface ListParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort?: string;
  dir?: 'asc' | 'desc';
  [key: string]: any;
}

export const withListParams = (params: ListParams = {}): ListParams => ({
  page: 1,
  per_page: 20,
  search: '',
  sort: '',
  dir: 'asc',
  ...params,
});
