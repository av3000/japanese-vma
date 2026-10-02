import { useCallback, useEffect, useMemo } from 'react';
import { Link, useParams, useSearchParams } from 'react-router-dom';
import { useCatalogueQuery } from '@/api/catalogues/details';
import {
	parseStudyConfig,
	readDeckFailure,
	studyConfigToSearchParams,
	useStudyDeck,
	type StudyConfig,
} from '@/api/flashcards/deck';
import { useSessionRecorder } from '@/api/flashcards/sessions';
import { StudySession } from '@/components/features/flashcards/StudySession';
import { StudySetupForm, type StudyDeckStatus } from '@/components/features/flashcards/StudySetupForm';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { PageHeader } from '@/components/shared/PageHeader';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { CATALOGUE_ROUTES, isCatalogueStudySupported } from '@/shared/constants/catalogues';
import { SavedListType } from '@/shared/constants/enums';
import styles from './CatalogueStudy.module.css';

const PLAY_PARAM = 'play';

/** The custom catalogue type a missed item of this catalogue can be saved into. */
const bookmarkTypeFor = (catalogueType: number): SavedListType | null => {
	switch (catalogueType) {
		case SavedListType.KANJIS:
		case SavedListType.KNOWNKANJIS:
			return SavedListType.KANJIS;
		case SavedListType.WORDS:
		case SavedListType.KNOWNWORDS:
			return SavedListType.WORDS;
		case SavedListType.RADICALS:
		case SavedListType.KNOWNRADICALS:
			return SavedListType.RADICALS;
		default:
			return null;
	}
};

/**
 * `/catalogues/:catalogueId/study` (epic #413). Public: visitors can play a public deck.
 * The study configuration lives in the URL, so a drill is a link; `play=1` switches from
 * the setup form to the session.
 */
const CatalogueStudyPage = () => {
	const { catalogueId } = useParams<{ catalogueId: string }>();
	const [searchParams, setSearchParams] = useSearchParams();
	const { data: catalogue, isPending, isError } = useCatalogueQuery(catalogueId);
	const { isAuthenticated } = useAuth();
	const catalogueType = catalogue?.type;
	const isSupported = catalogueType !== undefined && isCatalogueStudySupported(catalogueType);
	const isPlaying = searchParams.get(PLAY_PARAM) === '1';

	const config = useMemo(() => parseStudyConfig(searchParams, catalogueType), [searchParams, catalogueType]);
	const deckQuery = useStudyDeck(catalogueId, config, isSupported);
	const deck = deckQuery.data;
	const deckKey = deck ? `${deck.config.seed ?? 'seedless'}-${deck.cards.length}` : null;
	const recorder = useSessionRecorder(isAuthenticated, deckKey);

	// One saved session per played deck: start it when the session mounts (play=1 with a deck).
	// The recorder is keyed by the deck and starts only once, so re-renders are harmless.
	useEffect(() => {
		if (isPlaying && deck && catalogueId) {
			recorder.start(catalogueId, deck.config, deck.cards.length);
		}
	}, [isPlaying, deck, catalogueId, recorder]);

	const writeConfig = useCallback(
		(next: StudyConfig, play: boolean) => {
			setSearchParams(studyConfigToSearchParams(next, play ? { [PLAY_PARAM]: '1' } : {}), { replace: !play });
		},
		[setSearchParams],
	);

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

	const deckStatus: StudyDeckStatus = deckQuery.isError
		? { kind: 'error', ...readDeckFailure(deckQuery.error) }
		: deckQuery.data
			? {
					kind: 'ready',
					totalItems: deckQuery.data.totalItems,
					eligibleItems: deckQuery.data.eligibleItems,
					excludedEmptyAnswerField: deckQuery.data.excludedEmptyAnswerField,
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

				{isPlaying && deck ? (
					<StudySession
						// Remount on a new deck (another seed or config), so the run starts from card one.
						key={deckKey ?? undefined}
						deck={deck}
						catalogueHref={CATALOGUE_ROUTES.detail(catalogue.uuid)}
						onChangeSetup={() => writeConfig(config, false)}
						onAnswer={recorder.recordAttempt}
						onRoundComplete={(answers, attemptNo) => {
							// The score is the first pass; retry rounds are recorded as attempts only.
							if (attemptNo === 1) {
								void recorder.complete(answers.filter((answer) => answer.correct).length);
							}
						}}
						saveStatus={recorder.status}
						bookmarkCatalogueType={bookmarkTypeFor(catalogue.type)}
					/>
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
