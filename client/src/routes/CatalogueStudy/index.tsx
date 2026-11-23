import { useCallback, useEffect, useMemo } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import { useCatalogueQuery } from '@/api/catalogues/details';
import {
	isTypeableField,
	parseDeckError,
	parseStudyConfig,
	studyConfigToSearchParams,
	useStudyDeck,
	type StudyConfig,
} from '@/api/flashcards/deck';
import { StudySession } from '@/components/features/flashcards/StudySession';
import { StudySetupForm, type StudyDeckStatus } from '@/components/features/flashcards/StudySetupForm';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { PageHeader } from '@/components/shared/PageHeader';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { CATALOGUE_ROUTES, isCatalogueStudySupported, studyBookmarkTypeFor } from '@/shared/constants/catalogues';
import styles from './CatalogueStudy.module.css';
import { useStudyRun } from './useStudyRun';

const PLAY_PARAM = 'play';

/**
 * The setup form only needs the counts, which depend on prompt and answer alone. One card
 * in the cheapest mode keeps the preview from rebuilding a full deck with distractors on
 * every keystroke in "Cards per run"; the real deck is requested on Start.
 */
const previewConfigFor = (config: StudyConfig): StudyConfig => ({
	...config,
	count: 1,
	mode: isTypeableField(config.answer) ? 'typed' : 'options',
});

/**
 * `/catalogues/:catalogueId/study` (epic #413). Public: visitors can play a public deck.
 * The study configuration, seed included, lives in the URL, so a drill is a link and a
 * reload resumes the same deck; `play=1` switches from the setup form to the session.
 */
const CatalogueStudyPage = () => {
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const [searchParams, setSearchParams] = useSearchParams();
	const { data: catalogue, isPending, isError } = useCatalogueQuery(catalogueId);
	const { isAuthenticated } = useAuth();
	const catalogueType = catalogue?.type;
	const isSupported = catalogueType !== undefined && isCatalogueStudySupported(catalogueType);
	const isPlaying = searchParams.get(PLAY_PARAM) === '1';
	// "Study again" is a new run of the same deck: a new session on the server, a fresh component.

	const config = useMemo(() => parseStudyConfig(searchParams, catalogueType), [searchParams, catalogueType]);
	const previewQuery = useStudyDeck(catalogueId, previewConfigFor(config), isSupported && !isPlaying);
	const deckQuery = useStudyDeck(catalogueId, config, isSupported && isPlaying);
	const deck = deckQuery.data;
	const { runKey, recorder, restart } = useStudyRun({ catalogueId, deck, isPlaying, isAuthenticated });

	const writeConfig = useCallback(
		(next: StudyConfig, play: boolean, replace = !play) => {
			setSearchParams(studyConfigToSearchParams(next, play ? { [PLAY_PARAM]: '1' } : {}), { replace });
		},
		[setSearchParams],
	);

	// The first deck the server builds picks the seed; pin it in the URL so every later
	// request (reload, refocus, Start, back button) reproduces the same shuffle.
	const seededConfig = (deckQuery.data ?? previewQuery.data)?.config;
	useEffect(() => {
		if (config.seed === undefined && seededConfig?.seed !== undefined) {
			writeConfig({ ...config, seed: seededConfig.seed }, isPlaying, true);
		}
	}, [config, seededConfig, isPlaying, writeConfig]);

	// One saved session per run: start it when the session mounts (play=1 with a deck).
	// The recorder is keyed by deck and run and starts only once, so re-renders are harmless.
	if (!catalogueId || (isPending && !catalogue)) {
		return <PageLoading family="form" />;
	}

	if (isError || !catalogue) {
		return (
			<Container as="section" className={styles.notFound}>
				<p className="u-text-lead">Catalogue not found or was deleted.</p>
				<Button variant="linkButton" href={CATALOGUE_ROUTES.list}>
					Back to all Catalogues
				</Button>
			</Container>
		);
	}

	const deckStatus: StudyDeckStatus = previewQuery.isError
		? { kind: 'error', ...parseDeckError(previewQuery.error) }
		: previewQuery.data
			? {
					kind: 'ready',
					totalItems: previewQuery.data.totalItems,
					eligibleItems: previewQuery.data.eligibleItems,
					excludedEmptyAnswerField: previewQuery.data.excludedEmptyAnswerField,
				}
			: { kind: 'loading' };

	return (
		<Container size="sm" className={styles.page}>
			<Stack gap="lg">
				<div>
					<Link to={CATALOGUE_ROUTES.detail(catalogue.uuid)} className="tag-link">
						<Icon name="arrowDownSolid" rotate="90" size="sm" /> Back to {catalogue.title}
					</Link>
				</div>

				<PageHeader
					title={`Study: ${catalogue.title}`}
					meta={`${catalogue.type_label} · ${catalogue.items_count} items`}
				/>

				{isPlaying ? (
					deck ? (
						<StudySession
							key={runKey ?? undefined}
							deck={deck}
							catalogueHref={CATALOGUE_ROUTES.detail(catalogue.uuid)}
							onChangeSetup={() => writeConfig(config, false)}
							onRestart={restart}
							onAnswer={recorder.recordAttempt}
							onRoundComplete={(answers, attemptNo) => {
								// The score is the first pass; retry rounds are recorded as attempts only.
								if (attemptNo === 1) {
									void recorder.complete(answers.filter((answer) => answer.correct).length);
								}
							}}
							saveStatus={recorder.status}
							bookmarkCatalogueType={studyBookmarkTypeFor(catalogue.type)}
						/>
					) : deckQuery.isError ? (
						<StudySetupForm
							catalogueType={catalogue.type}
							value={config}
							onChange={(next) => writeConfig(next, false)}
							onStart={(next) => writeConfig(next, true)}
							deckStatus={{ kind: 'error', ...parseDeckError(deckQuery.error) }}
						/>
					) : (
						<PageLoading family="form" />
					)
				) : (
					<StudySetupForm
						catalogueType={catalogue.type}
						value={config}
						onChange={(next) => writeConfig(next, false)}
						onStart={(next) => writeConfig(next, true)}
						deckStatus={deckStatus}
					/>
				)}
			</Stack>
		</Container>
	);
};

export default CatalogueStudyPage;
