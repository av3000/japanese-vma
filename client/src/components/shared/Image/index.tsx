import * as React from 'react';
import { MediaInfo } from '@/types';

export interface ImageProps {
	readonly className?: string;
	readonly size180: MediaInfo;
	readonly size320?: MediaInfo;
	readonly size480?: MediaInfo;
	readonly size720?: MediaInfo;
	readonly size960?: MediaInfo;
	readonly size1280?: MediaInfo;
	readonly size1920?: MediaInfo;
	readonly responsiveSizesSettings?: string;
	readonly altText: string;
	readonly lazyLoading?: boolean;
	readonly width?: string | number;
	readonly height?: string | number;
}

/**
 * Generic image component with optional additional responsive and retina assets
 */
const Image: React.FunctionComponent<ImageProps> = ({
	className,
	size180,
	size320,
	size480,
	size720,
	size960,
	size1280,
	size1920,
	responsiveSizesSettings,
	altText,
	lazyLoading,
	width,
	height,
}) => {
	return (
		<img
			className={className}
			src={size180.url}
			srcSet={`
				${size1920?.url ? size1920?.url + ' 1920w,' : ''}
				${size1280?.url ? size1280?.url + ' 1280w,' : ''}
				${size960?.url ? size960?.url + ' 960w,' : ''}
				${size720?.url ? size720?.url + ' 720w,' : ''}
				${size480?.url ? size480?.url + ' 480w,' : ''}
				${size320?.url ? size320?.url + ' 320w,' : ''}
				${size180.url} 180w
			`}
			sizes={responsiveSizesSettings ?? '100vw'}
			alt={altText}
			// Width and height values are only used to calculate aspect ratio.
			// If values are set too small, they may limit the image size. But
			// on the other hand, if they are set too large, they will not
			// increase the image size, as the image size will be limited by the
			// container size.
			width={width !== undefined ? width : size180.width ? size180.width * 10 : undefined}
			height={height !== undefined ? height : size180.height ? size180.height * 10 : undefined}
			loading={lazyLoading ? 'lazy' : undefined}
		/>
	);
};

export default Image;
