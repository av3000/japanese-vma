import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { apiCall } from '@/services/api';
import { HttpMethod } from '@/shared/types';
import { addError } from './errorsSlice';

// Async thunks
export const fetchArticles = createAsyncThunk(
	'articles/fetchArticles',
	async (
		filters = {
			page: 1,
			per_page: 4,
			category: null,
			search: null,
			sort_by: 'created_at',
			sort_dir: 'DESC',
		},
		{ dispatch, rejectWithValue },
	) => {
		try {
			const cleanFilters = Object.entries(filters)
				.filter(([_, value]) => value !== null && value !== undefined)
				.reduce((obj, [key, value]) => ({ ...obj, [key]: value }), {});

			const queryParams = new URLSearchParams(cleanFilters).toString();
			const url = `/api/articles${queryParams ? `?${queryParams}` : ''}`;
			const res = await apiCall(HttpMethod.GET, url);

			// The response structure is:
			// { success: true, articles: { data: [...], total, ... }, message: "..." }
			return {
				articles: res.articles.data || [],
				paginationInfo: {
					current_page: res.articles.current_page,
					last_page: res.articles.last_page,
					next_page_url: res.articles.next_page_url,
					prev_page_url: res.articles.prev_page_url,
					total: res.articles.total,
				},
			};
		} catch (err) {
			dispatch(addError(err.message));
			return rejectWithValue(err.message);
		}
	},
);

export const removeArticle = createAsyncThunk(
	'articles/removeArticle',
	async (article_id, { dispatch, rejectWithValue }) => {
		try {
			await apiCall(HttpMethod.DELETE, `/api/article/${article_id}`);
			return article_id;
		} catch (err) {
			dispatch(addError(err.message));
			return rejectWithValue(err.message);
		}
	},
);

// Slice
const articlesSlice = createSlice({
	name: 'articles',
	initialState: {
		all: [],
		selectedArticle: null,
		loading: false,
		paginationInfo: {
			current_page: 1,
			last_page: 1,
			next_page_url: null,
			prev_page_url: null,
			total: 0,
		},
	},
	reducers: {
		setSelectedArticle: (state, action) => {
			state.selectedArticle = action.payload;
		},
	},
	extraReducers: (builder) => {
		builder
			// Handle fetchArticles
			.addCase(fetchArticles.pending, (state) => {
				state.loading = true;
			})
			.addCase(fetchArticles.fulfilled, (state, action) => {
				state.all = action.payload.articles;
				state.paginationInfo = action.payload.paginationInfo;
				state.loading = false;
			})
			.addCase(fetchArticles.rejected, (state) => {
				state.loading = false;
			})
			// Handle removeArticle
			.addCase(removeArticle.fulfilled, (state, action) => {
				state.all = state.all.filter((article) => article.id !== action.payload);
			});
	},
});

export const { setSelectedArticle } = articlesSlice.actions;
export default articlesSlice.reducer;
