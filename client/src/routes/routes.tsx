import React, { Suspense, lazy } from 'react';
import { Route, Routes } from 'react-router-dom';
import PrivateRoute from '@/helpers/PrivateRoute';
import CataloguesListSkeleton from '@/routes/CataloguesList/CatalogueListSkeleton/CataloguesListSkeleton';
import HomePage from '@/routes/Homepage';

// Lazy-loaded page components
const PageNotFound = lazy(() => import('@/routes/NotFound'));

// Auth routes
const LoginPage = lazy(() => import('@/routes/Login'));
const RegisterPage = lazy(() => import('@/routes/Register'));

// Article routes
const ArticlesListPage = lazy(() => import('@/routes/ArticlesList'));
const ArticleDetailsPage = lazy(() => import('@/routes/ArticleDetails'));
const ArticleCreatePage = lazy(() => import('@/routes/ArticleCreate'));
const ArticleEditPage = lazy(() => import('@/routes/ArticleEdit'));

// Catalogue routes
const CataloguesListPage = lazy(() => import('@/routes/CataloguesList'));
const CatalogueDetailsPage = lazy(() => import('@/routes/CatalogueDetails'));
const CatalogueCreatePage = lazy(() => import('@/routes/CatalogueCreate'));
const CatalogueEditPage = lazy(() => import('@/routes/CatalogueEdit'));
const CatalogueLegacyRedirectsPage = lazy(() => import('@/routes/CatalogueLegacyRedirects'));

// Japanese learning routes
const RadicalsPage = lazy(() => import('@/routes/japanese/RadicalsList'));
const RadicalDetailsPage = lazy(() => import('@/routes/japanese/RadicalDetails'));
const KanjisPage = lazy(() => import('@/routes/japanese/KanjisList'));
const KanjiDetailsPage = lazy(() => import('@/routes/japanese/KanjiDetails'));
const WordsPage = lazy(() => import('@/routes/japanese/WordsList'));
const WordDetailsPage = lazy(() => import('@/routes/japanese/WordDetails'));
const SentencesPage = lazy(() => import('@/routes/japanese/SentencesList'));
const SentenceDetailsPage = lazy(() => import('@/routes/japanese/SentenceDetails'));

// Community routes
const CommunityPage = lazy(() => import('@/routes/community/PostsList'));
const PostDetailsPage = lazy(() => import('@/routes/community/PostDetails'));
const PostFormPage = lazy(() => import('@/routes/community/PostForm'));
const PostEditPage = lazy(() => import('@/routes/community/PostEdit'));

// Dashboard routes
const DashboardPage = lazy(() => import('@/routes/Dashboard'));

const SuspenseWrapper = ({ children, fallback = null }: { children: React.ReactNode; fallback?: React.ReactNode }) => (
	<Suspense fallback={fallback}>{children}</Suspense>
);

const AppRoutes: React.FC = () => {
	return (
		<Routes>
			{/* Public routes */}
			<Route path="/" element={<HomePage />} />
			<Route
				path="/login"
				element={
					<SuspenseWrapper>
						<LoginPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/register"
				element={
					<SuspenseWrapper>
						<RegisterPage />
					</SuspenseWrapper>
				}
			/>

			{/* Articles */}
			<Route
				path="/articles"
				element={
					<SuspenseWrapper>
						<ArticlesListPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/articles/:article_id"
				element={
					<SuspenseWrapper>
						<ArticleDetailsPage />
					</SuspenseWrapper>
				}
			/>

			{/* Catalogues */}
			<Route
				path="/catalogues"
				element={
					<SuspenseWrapper fallback={<CataloguesListSkeleton />}>
						<CataloguesListPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/catalogues/:catalogueId"
				element={
					<SuspenseWrapper>
						<CatalogueDetailsPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/lists"
				element={
					<SuspenseWrapper>
						<CatalogueLegacyRedirectsPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/list/:catalogueId"
				element={
					<SuspenseWrapper>
						<CatalogueLegacyRedirectsPage />
					</SuspenseWrapper>
				}
			/>

			{/* Japanese learning resources */}
			<Route
				path="/radicals"
				element={
					<SuspenseWrapper>
						<RadicalsPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/radical/:radical_id"
				element={
					<SuspenseWrapper>
						<RadicalDetailsPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/kanjis"
				element={
					<SuspenseWrapper>
						<KanjisPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/kanji/:kanji_id"
				element={
					<SuspenseWrapper>
						<KanjiDetailsPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/words"
				element={
					<SuspenseWrapper>
						<WordsPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/word/:word_id"
				element={
					<SuspenseWrapper>
						<WordDetailsPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/sentences"
				element={
					<SuspenseWrapper>
						<SentencesPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/sentence/:sentence_id"
				element={
					<SuspenseWrapper>
						<SentenceDetailsPage />
					</SuspenseWrapper>
				}
			/>

			{/* Community */}
			<Route
				path="/community"
				element={
					<SuspenseWrapper>
						<CommunityPage />
					</SuspenseWrapper>
				}
			/>
			<Route
				path="/community/:post_id"
				element={
					<SuspenseWrapper>
						<PostDetailsPage />
					</SuspenseWrapper>
				}
			/>

			{/* Protected routes */}
			<Route element={<PrivateRoute />}>
				<Route
					path="/newarticle"
					element={
						<SuspenseWrapper>
							<ArticleCreatePage />
						</SuspenseWrapper>
					}
				/>
				<Route
					path="/article/edit/:article_id"
					element={
						<SuspenseWrapper>
							<ArticleEditPage />
						</SuspenseWrapper>
					}
				/>
				<Route
					path="/catalogues/new"
					element={
						<SuspenseWrapper>
							<CatalogueCreatePage />
						</SuspenseWrapper>
					}
				/>
				<Route
					path="/catalogues/:catalogueId/edit"
					element={
						<SuspenseWrapper>
							<CatalogueEditPage />
						</SuspenseWrapper>
					}
				/>
				<Route
					path="/newlist"
					element={
						<SuspenseWrapper>
							<CatalogueLegacyRedirectsPage />
						</SuspenseWrapper>
					}
				/>
				<Route
					path="/list/edit/:catalogueId"
					element={
						<SuspenseWrapper>
							<CatalogueLegacyRedirectsPage />
						</SuspenseWrapper>
					}
				/>
				<Route
					path="/newpost"
					element={
						<SuspenseWrapper>
							<PostFormPage />
						</SuspenseWrapper>
					}
				/>
				<Route
					path="/community/edit/:post_id"
					element={
						<SuspenseWrapper>
							<PostEditPage />
						</SuspenseWrapper>
					}
				/>
				<Route
					path="/dashboard"
					element={
						<SuspenseWrapper>
							<DashboardPage />
						</SuspenseWrapper>
					}
				/>
			</Route>

			{/* Catch-all for 404 */}
			<Route
				path="*"
				element={
					<SuspenseWrapper>
						<PageNotFound />
					</SuspenseWrapper>
				}
			/>
		</Routes>
	);
};

export default AppRoutes;
