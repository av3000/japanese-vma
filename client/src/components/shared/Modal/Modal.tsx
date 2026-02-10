import * as React from 'react';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import styles from './Modal.module.scss';

export type ModalSize = 'sm' | 'md' | 'lg' | 'fullscreen';

// Size classes are mapped explicitly to avoid runtime capitalization and keep class names predictable.
const sizeClassMap: Record<ModalSize, string> = {
	sm: styles['size-sm'],
	md: styles['size-md'],
	lg: styles['size-lg'],
	fullscreen: styles['size-fullscreen'],
};

interface ModalContextValue {
	titleId?: string;
	bodyId?: string;
	registerTitleId: (id: string | undefined) => void;
	registerBodyId: (id: string | undefined) => void;
	handleClose: () => void;
	closeLabel: string;
}

const ModalContext = React.createContext<ModalContextValue | null>(null);

const useModalContext = (componentName: string): ModalContextValue => {
	const context = React.useContext(ModalContext);
	if (!context) {
		throw new Error(`${componentName} must be used within Modal.`);
	}
	return context;
};

export interface ModalProps {
	id: string;
	dialogRef: React.RefObject<HTMLDialogElement | null>;
	handleClose: () => void;
	children: React.ReactNode;
	size?: ModalSize;
	closeLabel?: string;
	closeOnBackdrop?: boolean;
	isOpen?: boolean;
	ariaLabel?: string;
	className?: string;
}

export interface ModalHeaderProps extends React.HTMLAttributes<HTMLDivElement> {
	showCloseButton?: boolean;
}

export type ModalTitleProps = React.HTMLAttributes<HTMLHeadingElement>;

export type ModalBodyProps = React.HTMLAttributes<HTMLDivElement>;

// eslint-disable-next-line @typescript-eslint/no-empty-object-type
export interface ModalFooterProps extends React.HTMLAttributes<HTMLDivElement> {}

const ModalHeader: React.FunctionComponent<ModalHeaderProps> = ({
	children,
	className,
	showCloseButton = true,
	...props
}) => {
	const { handleClose, closeLabel } = useModalContext('Modal.Header');

	return (
		<div className={classNames(styles.header, className)} {...props}>
			<div className={styles['header-content']}>{children}</div>
			{showCloseButton && (
				<Button
					className={styles['close-button']}
					variant="ghost"
					hasOnlyIcon
					aria-label={closeLabel}
					onClick={handleClose}
				>
					<Icon name="removeSolid" size="md" />
				</Button>
			)}
		</div>
	);
};

// eslint-disable-next-line react/prop-types
const ModalTitle: React.FunctionComponent<ModalTitleProps> = ({ className, id: idProp, children, ...props }) => {
	const { registerTitleId } = useModalContext('Modal.Title');
	const autoId = React.useId();
	const id = idProp ?? `modal-title-${autoId}`;

	// Registering the title id keeps aria-labelledby wired up even when the title is optional.
	React.useEffect(() => {
		registerTitleId(id);
		return () => registerTitleId(undefined);
	}, [id, registerTitleId]);

	return (
		<h2 id={id} className={classNames(styles.title, className)} {...props}>
			{children}
		</h2>
	);
};

// eslint-disable-next-line react/prop-types
const ModalBody: React.FunctionComponent<ModalBodyProps> = ({ className, id: idProp, children, ...props }) => {
	const { registerBodyId } = useModalContext('Modal.Body');
	const autoId = React.useId();
	const id = idProp ?? `modal-body-${autoId}`;

	// Same as the title: keep aria-describedby in sync without relying on dynamic class names.
	React.useEffect(() => {
		registerBodyId(id);
		return () => registerBodyId(undefined);
	}, [id, registerBodyId]);

	return (
		<div id={id} className={classNames(styles.body, className)} {...props}>
			{children}
		</div>
	);
};

const ModalFooter: React.FunctionComponent<ModalFooterProps> = ({ className, ...props }) => {
	return <div className={classNames(styles.footer, className)} {...props} />;
};

type ModalCompoundComponent = React.FunctionComponent<ModalProps> & {
	Header: React.FunctionComponent<ModalHeaderProps>;
	Title: React.FunctionComponent<ModalTitleProps>;
	Body: React.FunctionComponent<ModalBodyProps>;
	Footer: React.FunctionComponent<ModalFooterProps>;
};

export const Modal = (({
	id,
	dialogRef,
	handleClose,
	children,
	size = 'md',
	closeLabel = 'Close dialog',
	closeOnBackdrop = true,
	isOpen,
	ariaLabel,
	className,
}: ModalProps) => {
	const [titleId, setTitleId] = React.useState<string | undefined>();
	const [bodyId, setBodyId] = React.useState<string | undefined>();

	const contextValue = React.useMemo<ModalContextValue>(
		() => ({
			titleId,
			bodyId,
			registerTitleId: setTitleId,
			registerBodyId: setBodyId,
			handleClose,
			closeLabel,
		}),
		[titleId, bodyId, handleClose, closeLabel],
	);

	const sizeClassName = sizeClassMap[size];
	const state = isOpen === undefined ? undefined : isOpen ? 'open' : 'closed';
	const fallbackAriaLabel = titleId ? undefined : (ariaLabel ?? 'Dialog');

	return (
		<ModalContext.Provider value={contextValue}>
			<dialog
				id={id}
				ref={dialogRef}
				className={classNames(styles.dialog, className)}
				aria-modal="true"
				aria-labelledby={titleId}
				aria-describedby={bodyId}
				aria-label={fallbackAriaLabel}
				data-state={state}
			>
				{/* Explicit backdrop lets us control click-to-close without relying on dialog defaults. */}
				<div
					className={styles.backdrop}
					aria-hidden="true"
					onClick={closeOnBackdrop ? handleClose : undefined}
				/>
				<div className={classNames(styles.panel, sizeClassName)}>{children}</div>
			</dialog>
		</ModalContext.Provider>
	);
}) as ModalCompoundComponent;

Modal.Header = ModalHeader;
Modal.Title = ModalTitle;
Modal.Body = ModalBody;
Modal.Footer = ModalFooter;
