import React from 'react';

import classNames from 'classnames';

import { capitalize } from '@/helpers';

import { getElemCSSVars } from '../settingsGetter';
import styles from './SettingGroup.module.scss';

interface SettingGroupProps {
  variables: string;
  variant?: 'color' | 'radius' | 'spacing' | 'typography' | 'zIndex';
  hasNoTitle?: boolean;
}

export const SettingGroup: React.FunctionComponent<SettingGroupProps> = ({
  variables,
  variant,
  hasNoTitle,
}) => {
  const cssVariables = Object.entries(getElemCSSVars(variables));

  const stripZIndex = (value: string): number => {
    const stripped = value.replace(/[()]/g, '').replace(/calc/g, '').replace(/ /g, '');
    return eval(stripped);
  };

  return (
    <article
      className={classNames(styles.wrapper, variant && styles['variant' + capitalize(variant)])}
    >
      {hasNoTitle !== true && <h3>{variables}</h3>}

      {cssVariables.length ? (
        <table>
          <tbody>
            {cssVariables.map((item, key) => (
              <tr className={styles.itemRow} key={key}>
                {variant && variant !== 'zIndex' && (
                  <td>
                    <div
                      className={styles.extraCell}
                      style={
                        {
                          '--bg-color': variant === 'color' ? item[1] : undefined,
                          '--font-size': variant === 'typography' ? item[1] : undefined,
                          '--radius': variant === 'radius' ? item[1] : undefined,
                          '--size': variant === 'spacing' ? item[1] : undefined,
                        } as React.CSSProperties
                      }
                    >
                      {variant === 'typography' && <>Lorem ipsum dolor sit</>}
                    </div>
                  </td>
                )}
                <td>
                  <strong>{item[0]}</strong>
                </td>
                <td>{variant === 'zIndex' ? stripZIndex(item[1]) : item[1].trim()}</td>
              </tr>
            ))}
          </tbody>
        </table>
      ) : (
        <p className={styles.errorText}>No results for {`'${variables}'`}.</p>
      )}
    </article>
  );
};
