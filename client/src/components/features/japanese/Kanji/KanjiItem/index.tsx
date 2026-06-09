import React from 'react';

import { Link } from '@/components/shared/Link';

interface KanjiItemProps {
	uuid: string;
	character: string;
	strokeCount: number;
	onyomi: string;
	kunyomi: string;
	meaning: string;
	frequency: string;
	jlpt: string;
	parts: string;
}

const KanjiItem: React.FC<KanjiItemProps> = ({
	uuid,
	character,
	strokeCount,
	onyomi,
	kunyomi,
	meaning,
	frequency,
	jlpt,
	parts,
}) => {
	return (
		<div className="post-preview">
			<div className="post-title">
				<h1>{character}</h1>
			</div>
			<div className="post-subtitle">
				<h3>{meaning}</h3>
			</div>
			<div className="row">
				<div className="col-md-6">
					<p>
						onyomi: {onyomi}, <br /> kunyomi: {kunyomi}
					</p>
				</div>
				<div className="col-md-3">
					<p>
						frequency: {frequency}, <br /> jlpt: {jlpt}
					</p>
				</div>
				<div className="col-md-3">
					<p>
						parts: {parts}, <br /> stroke_count: {strokeCount}
						<span className="float-right">
							<Link to={`/kanji/${uuid}`}>Open</Link>
						</span>
					</p>
				</div>
			</div>
			<hr />
		</div>
	);
};

export default KanjiItem;
