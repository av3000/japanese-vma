import * as React from 'react';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import styles from './DialogModal.module.scss';

export type DialogModalSize = 'sm' | 'md' | 'lg' | 'fullscreen';

const sizeClassMap: Record<DialogModalSize, string> = {
	sm: styles['size-sm'],
	md: styles['size-md'],
	lg: styles['size-lg'],
	fullscreen: styles['size-fullscreen'],
};

export interface DialogModalProps {
	id: string;
	dialogRef: React.RefObject<HTMLDialogElement | null>;
	onClose: () => void;
	children: React.ReactNode;
	size?: DialogModalSize;
	closeLabel?: string;
	closeOnBackdrop?: boolean;
	isOpen?: boolean;
	ariaLabel?: string;
	className?: string;
}

export type DialogModalHeaderProps = React.HTMLAttributes<HTMLDivElement>;

export type DialogModalTitleProps = React.HTMLAttributes<HTMLHeadingElement>;

export type DialogModalBodyProps = React.HTMLAttributes<HTMLDivElement>;

export type DialogModalFooterProps = React.HTMLAttributes<HTMLDivElement>;

const DialogModalHeader: React.FunctionComponent<DialogModalHeaderProps> = ({ children, className, ...props }) => {
	return (
		<div className={classNames(styles.header, className)} {...props}>
			<div className={styles['header-content']}>{children}</div>
		</div>
	);
};

const DialogModalTitle: React.FunctionComponent<DialogModalTitleProps> = ({
	className,
	id: idProp,
	children,
	...props
}) => {
	return (
		<h2 id={idProp} className={classNames(styles.title, className)} {...props}>
			{children}
		</h2>
	);
};

const DialogModalBody: React.FunctionComponent<DialogModalBodyProps> = ({
	className,
	id: idProp,
	children,
	...props
}) => {
	return (
		<div id={idProp} className={classNames(styles.body, className)} {...props}>
			{children}
		</div>
	);
};

const DialogModalFooter: React.FunctionComponent<DialogModalFooterProps> = ({ className, ...props }) => {
	return <div className={classNames(styles.footer, className)} {...props} />;
};

type DialogModalCompoundComponent = React.FunctionComponent<DialogModalProps> & {
	Header: React.FunctionComponent<DialogModalHeaderProps>;
	Title: React.FunctionComponent<DialogModalTitleProps>;
	Body: React.FunctionComponent<DialogModalBodyProps>;
	Footer: React.FunctionComponent<DialogModalFooterProps>;
};

export const DialogModal = (({
	id,
	dialogRef,
	onClose,
	children,
	size = 'md',
	closeLabel = 'Close dialog',
	closeOnBackdrop = true,
	isOpen,
	ariaLabel,
	className,
}: DialogModalProps) => {
	const childArray = React.Children.toArray(children);
	const hasHeader = childArray.some((child) => React.isValidElement(child) && child.type === DialogModalHeader);
	const hasBody = childArray.some((child) => React.isValidElement(child) && child.type === DialogModalBody);
	const hasFooter = childArray.some((child) => React.isValidElement(child) && child.type === DialogModalFooter);
	const isSimpleLayout = !hasHeader && !hasBody && !hasFooter;
	const autoBodyId = React.useId();

	const sizeClassName = sizeClassMap[size];
	const state = isOpen === undefined ? undefined : isOpen ? 'open' : 'closed';
	const ariaLabelValue = ariaLabel ?? 'Dialog';
	const describedBy = isSimpleLayout ? autoBodyId : undefined;
	const content = isSimpleLayout ? (
		<div id={autoBodyId} className={classNames(styles.body, styles['body-with-close'])}>
			{children}
		</div>
	) : (
		children
	);

	return (
		<dialog
			id={id}
			ref={dialogRef}
			className={classNames(styles.dialog, className)}
			aria-modal="true"
			aria-describedby={describedBy}
			aria-label={ariaLabelValue}
			data-state={state}
		>
			<div className={styles.backdrop} aria-hidden="true" onClick={closeOnBackdrop ? onClose : undefined} />
			<div className={classNames(styles.panel, sizeClassName)}>
				<Button
					className={styles['close-floating']}
					variant="ghost"
					hasOnlyIcon
					aria-label={closeLabel}
					onClick={onClose}
				>
					<Icon name="removeSolid" size="md" />
				</Button>
				{content}
			</div>
		</dialog>
	);
}) as DialogModalCompoundComponent;

DialogModal.Header = DialogModalHeader;
DialogModal.Title = DialogModalTitle;
DialogModal.Body = DialogModalBody;
DialogModal.Footer = DialogModalFooter;
