import * as React from 'react';
import classNames from 'classnames';
import { Popover, PopoverContent, PopoverHeader, PopoverTitle, PopoverTrigger } from '@/components/ui/popover';
import { useWebSocket } from '@/providers/contexts/socket-provider';
import styles from './SocketStatusIndicator.module.css';

type StatusVariant = 'success' | 'pending' | 'destructive';

const SocketStatusIndicator: React.FC = () => {
	const { connectionStatus, lastError, connectionInfo, hasAttemptedConnection, isConfigured } = useWebSocket();
	const [isOpen, setIsOpen] = React.useState(false);
	const [isPinned, setIsPinned] = React.useState(false);

	const { variant, label } = React.useMemo<{ variant: StatusVariant; label: string }>(() => {
		switch (connectionStatus) {
			case 'connected':
				return { variant: 'success', label: 'connected' };
			case 'connecting':
			case 'reconnecting':
				return { variant: 'pending', label: connectionStatus };
			case 'failed':
			case 'disconnected':
			default:
				return { variant: 'destructive', label: connectionStatus };
		}
	}, [connectionStatus]);

	if (!isConfigured) {
		return null;
	}

	const statusText = hasAttemptedConnection ? label : 'initializing';

	// Hover and focus preview the popover; click (or keyboard activation) pins it open
	// so the details can be read or copied. Radix handles Escape and outside clicks.
	const handleOpenChange = (open: boolean) => {
		setIsOpen(open);
		if (!open) setIsPinned(false);
	};

	return (
		<Popover open={isOpen} onOpenChange={handleOpenChange}>
			<PopoverTrigger asChild>
				<button
					type="button"
					className={styles.trigger}
					aria-label={`WebSocket ${statusText}`}
					onPointerEnter={() => setIsOpen(true)}
					onPointerLeave={() => !isPinned && setIsOpen(false)}
					onFocus={() => setIsOpen(true)}
					onBlur={() => !isPinned && setIsOpen(false)}
					onClick={() => {
						setIsPinned((pinned) => !pinned);
						setIsOpen(true);
					}}
				>
					<span aria-hidden="true" className={classNames(styles.dot, styles[variant])} />
				</button>
			</PopoverTrigger>
			<PopoverContent
				align="end"
				className={styles.content}
				onOpenAutoFocus={(event) => event.preventDefault()}
				onPointerEnter={() => setIsOpen(true)}
				onPointerLeave={() => !isPinned && setIsOpen(false)}
			>
				<PopoverHeader>
					<PopoverTitle>WebSocket</PopoverTitle>
				</PopoverHeader>
				<dl className={styles.details}>
					<dt>Status</dt>
					<dd>{statusText}</dd>
					<dt>Target</dt>
					<dd>
						{connectionInfo.scheme}://{connectionInfo.host}:{connectionInfo.port}
					</dd>
					{lastError && (
						<>
							<dt>Last error</dt>
							<dd>{lastError}</dd>
						</>
					)}
				</dl>
			</PopoverContent>
		</Popover>
	);
};

export default SocketStatusIndicator;
