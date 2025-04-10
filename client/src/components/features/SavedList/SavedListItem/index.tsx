import React from "react";
import DefaultArticleImg from "@/assets/images/smartphone-screen-with-art-photo-gallery-application-3850271-mid.jpg";
import Hashtags from "@/components/ui/hashtags";
import { Link } from "@/components/shared/Link";

interface Hashtag {
  id: string | number;
  content: string;
}

interface ListItemProps {
  id: string | number;
  created_at: string;
  title: string;
  listType: string;
  type: number;
  commentsTotal: number;
  itemsTotal: number;
  viewsTotal: number;
  likesTotal: number;
  downloadsTotal: number;
  hashtags: Hashtag[];
  n1?: number;
  n2?: number;
  n3?: number;
  n4?: number;
  n5?: number;
  uncommon?: number;
}

const SavedListItem: React.FC<ListItemProps> = ({
  id,
  created_at,
  title,
  listType,
  type,
  commentsTotal,
  itemsTotal,
  viewsTotal,
  likesTotal,
  downloadsTotal,
  hashtags,
  n1 = 0,
  n2 = 0,
  n3 = 0,
  n4 = 0,
  n5 = 0,
  uncommon = 0,
}) => {
  const isJlptList = type === 2 || type === 6;

  return (
    <div className="col-lg-4 col-md-6 col-sm-8">
      <div className="card mb-4 shadow-sm">
        <Link to={`/list/${id}`}>
          <img
            src={DefaultArticleImg}
            alt="article-logo"
            height="225"
            width="100%"
            className="timelines-image hover"
          />
        </Link>
        <div className="card-body">
          <Link to={`/list/${id}`} className="article-title-link">
            <h4 className="card-text article-title">{title}</h4>
          </Link>
          <br />
          <strong>{listType}</strong>
          <Hashtags hashtags={hashtags} />
          <p className="text-muted">{created_at}</p>
          <p className="text-muted">
            {viewsTotal} views &nbsp;
            {commentsTotal} comments &nbsp;
            {likesTotal} likes &nbsp;
            {downloadsTotal} downloads &nbsp;
            <br />
            {itemsTotal} items &nbsp;
          </p>

          {isJlptList && (
            <>
              <hr />
              <div className="d-flex justify-content-between align-items-center text-muted">
                <ruby className="h4 mr-2">
                  {n1}
                  <rp>(</rp>
                  <rt>N1</rt>
                  <rp>)</rp>
                </ruby>
                <ruby className="h4 mr-2">
                  {n2}
                  <rp>(</rp>
                  <rt>N2</rt>
                  <rp>)</rp>
                </ruby>
                <ruby className="h4 mr-2">
                  {n3}
                  <rp>(</rp>
                  <rt>N3</rt>
                  <rp>)</rp>
                </ruby>
                <ruby className="h4 mr-2">
                  {n4}
                  <rp>(</rp>
                  <rt>N4</rt>
                  <rp>)</rp>
                </ruby>
                <ruby className="h4 mr-2">
                  {n5}
                  <rp>(</rp>
                  <rt>N5</rt>
                  <rp>)</rp>
                </ruby>
                <ruby className="h4 mr-2">
                  {uncommon}
                  <rp>(</rp>
                  <rt>NA</rt>
                  <rp>)</rp>
                </ruby>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default SavedListItem;
