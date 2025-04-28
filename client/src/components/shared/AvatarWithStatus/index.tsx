import { Avatar, AvatarProps } from '../Avatar';
import { Badge, BadgeColorType } from '../Badge';
import styles from './AvatarWithStatus.module.scss';

const AvatarWithStatus = ({
	userId,
	status,
	...avatarProps
}: { userId: string; status: BadgeColorType } & AvatarProps) => {
	// @ts-ignore
	const fetchStatus = (userId): void => {
		// call or poll for status of health
	};

	return (
		<div className={styles.wrapper}>
			<Badge
				variant="dot"
				color={status}
				anchorOrigin={{ vertical: 'top', horizontal: 'right' }}
				className={styles.statusIndicator}
			>
				<Avatar {...avatarProps} />
			</Badge>
		</div>
	);
};

export default AvatarWithStatus;
