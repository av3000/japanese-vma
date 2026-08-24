import React from 'react';
import { Route, Routes } from 'react-router-dom';
import PrivateRoute from '@/helpers/PrivateRoute';
import ArticleDetailsSkeleton from '@/routes/ArticleDetails/ArticleDetailsSkeleton';
import ArticlesListSkeleton from '@/routes/ArticlesList/ArticlesListSkeleton/ArticlesListSkeleton';
import CataloguesListSkeleton from '@/routes/CataloguesList/CatalogueListSkeleton/CataloguesListSkeleton';
import HomePage from '@/routes/Homepage';
import { createLazyRoute } from '@/routes/routeLoading/createLazyRoute';

const PageNotFound = createLazyRoute(() => import('@/routes/NotFound'));

const LoginPage = createLazyRoute(() => import('@/routes/Login'), { family: 'form' });
const RegisterPage = createLazyRoute(() => import('@/routes/Register'), { family: 'form' });

const ArticlesListPage = createLazyRoute(() => import('@/routes/ArticlesList'), {
	family: 'list',
	visual: <ArticlesListSkeleton />,
});
const ArticleDetailsPage = createLazyRoute(() => import('@/routes/ArticleDetails'), {
	family: 'detail',
	visual: <ArticleDetailsSkeleton />,
});
const ArticleCreatePage = createLazyRoute(() => import('@/routes/ArticleCreate'), { family: 'form' });
const ArticleEditPage = createLazyRoute(() => import('@/routes/ArticleEdit'), { family: 'form' });

const CataloguesListPage = createLazyRoute(() => import('@/routes/CataloguesList'), {
	family: 'list',
	visual: <CataloguesListSkeleton />,
});
const CatalogueDetailsPage = createLazyRoute(() => import('@/routes/CatalogueDetails'), { family: 'detail' });
const CatalogueCreatePage = createLazyRoute(() => import('@/routes/CatalogueCreate'), { family: 'form' });
const CatalogueEditPage = createLazyRoute(() => import('@/routes/CatalogueEdit'), { family: 'form' });
const CatalogueLegacyRedirectsPage = createLazyRoute(() => import('@/routes/CatalogueLegacyRedirects'));

const RadicalsPage = createLazyRoute(() => import('@/routes/japanese/RadicalsList'), { family: 'generic' });
const RadicalDetailsPage = createLazyRoute(() => import('@/routes/japanese/RadicalDetails'), {
	family: 'detail',
});
const KanjisPage = createLazyRoute(() => import('@/routes/japanese/KanjisList'), { family: 'list' });
const KanjiDetailsPage = createLazyRoute(() => import('@/routes/japanese/KanjiDetails'), { family: 'detail' });
const WordsPage = createLazyRoute(() => import('@/routes/japanese/WordsList'), { family: 'list' });
const WordDetailsPage = createLazyRoute(() => import('@/routes/japanese/WordDetails'), { family: 'detail' });
const SentencesPage = createLazyRoute(() => import('@/routes/japanese/SentencesList'), { family: 'list' });
const SentenceDetailsPage = createLazyRoute(() => import('@/routes/japanese/SentenceDetails'), {
	family: 'detail',
});

const CommunityPage = createLazyRoute(() => import('@/routes/community/PostsList'), { family: 'list' });
const PostDetailsPage = createLazyRoute(() => import('@/routes/community/PostDetails'), { family: 'detail' });
const PostFormPage = createLazyRoute(() => import('@/routes/community/PostForm'), { family: 'form' });
const PostEditPage = createLazyRoute(() => import('@/routes/community/PostEdit'), { family: 'form' });

const DashboardPage = createLazyRoute(() => import('@/routes/Dashboard'), { family: 'dashboard' });

const AppRoutes: React.FC = () => (
	<Routes>
		<Route path="/" element={<HomePage />} />
		<Route path="/login" element={<LoginPage />} />
		<Route path="/register" element={<RegisterPage />} />

		<Route path="/articles" element={<ArticlesListPage />} />
		<Route path="/articles/:article_id" element={<ArticleDetailsPage />} />

		<Route path="/catalogues" element={<CataloguesListPage />} />
		<Route path="/catalogues/:catalogueId" element={<CatalogueDetailsPage />} />
		<Route path="/lists" element={<CatalogueLegacyRedirectsPage />} />
		<Route path="/list/:catalogueId" element={<CatalogueLegacyRedirectsPage />} />

		<Route path="/radicals" element={<RadicalsPage />} />
		<Route path="/radical/:radical_id" element={<RadicalDetailsPage />} />
		<Route path="/kanjis" element={<KanjisPage />} />
		<Route path="/kanji/:kanji_id" element={<KanjiDetailsPage />} />
		<Route path="/words" element={<WordsPage />} />
		<Route path="/word/:word_id" element={<WordDetailsPage />} />
		<Route path="/sentences" element={<SentencesPage />} />
		<Route path="/sentence/:sentence_id" element={<SentenceDetailsPage />} />

		<Route path="/community" element={<CommunityPage />} />
		<Route path="/community/:post_id" element={<PostDetailsPage />} />

		<Route element={<PrivateRoute />}>
			<Route path="/newarticle" element={<ArticleCreatePage />} />
			<Route path="/article/edit/:article_id" element={<ArticleEditPage />} />
			<Route path="/catalogues/new" element={<CatalogueCreatePage />} />
			<Route path="/catalogues/:catalogueId/edit" element={<CatalogueEditPage />} />
			<Route path="/newlist" element={<CatalogueLegacyRedirectsPage />} />
			<Route path="/list/edit/:catalogueId" element={<CatalogueLegacyRedirectsPage />} />
			<Route path="/newpost" element={<PostFormPage />} />
			<Route path="/community/edit/:post_id" element={<PostEditPage />} />
			<Route path="/dashboard" element={<DashboardPage />} />
		</Route>

		<Route path="*" element={<PageNotFound />} />
	</Routes>
);

export default AppRoutes;
