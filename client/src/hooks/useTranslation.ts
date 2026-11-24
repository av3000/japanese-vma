import { useContext } from 'react';
import { TranslationContext } from '@/providers/contexts/translation-provider';

/**
 * `t(key)`, the selected locale, the active locales, `setLocale` and the load error. Outside a
 * `TranslationProvider` it serves English from the snapshot, so a component test needs no setup.
 */
export const useTranslation = () => useContext(TranslationContext);
