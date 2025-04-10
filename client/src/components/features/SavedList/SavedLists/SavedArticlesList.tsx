import React from "react";
import Hashtags from "../../../ui/hashtags";
import { Link } from "@/components/shared/Link";
import { Button } from "@/components/shared/Button";
import { Icon } from "@/components/shared/Icon";
import sharedStyles from "../SharedListStyles.module.scss";

interface Article {
  id: string | number;
  title_jp: string;
  hashtags: string[];
  viewsTotal: number;
  savesTotal: number;
  downloadsTotal: number;
  commentsTotal: number;
  likesTotal: number;
}

interface User {
  user: {
    id: string | number;
  };
}

interface SavedArticlesListProps {
  objects: Article[];
  removeFromList: (id: string | number) => void;
  currentUser: User;
  listUserId: string | number;
}

const SavedArticlesList: React.FC<SavedArticlesListProps> = ({
  objects,
  removeFromList,
  currentUser,
  listUserId,
}) => {
  return (
    <div className={sharedStyles.listContainer}>
      {objects.map((article) => {
        const hashtags = article.hashtags.slice(0, 3);
        return (
          <div key={article.id} className={sharedStyles.itemCard}>
            <div className={sharedStyles.itemHeader}>
              <h3 className={sharedStyles.articleTitle}>
                <Link to={`/article/${article.id}`} target="_blank">
                  {article.title_jp}
                  <Icon size="sm" name="externalLink" />
                </Link>
              </h3>
              {currentUser.user.id === listUserId && (
                <Button
                  type="button"
                  size="md"
                  variant="danger"
                  onClick={() => removeFromList(article.id)}
                  className={sharedStyles.removeButton}
                >
                  <Icon size="sm" name="minusSolid" />
                </Button>
              )}
            </div>

            <div className={sharedStyles.itemDetails}>
              <Hashtags hashtags={hashtags} />
            </div>

            <div className={sharedStyles.metaInfo}>
              <div className={sharedStyles.statItem}>
                <Icon
                  size="sm"
                  name="eyeRegular"
                  className={sharedStyles.statIcon}
                />
                <span>{article.viewsTotal}</span>
              </div>
              <div className={sharedStyles.statItem}>
                <Icon
                  size="sm"
                  name="bookmarkRegular"
                  className={sharedStyles.statIcon}
                />
                <span>{article.savesTotal}</span>
              </div>
              <div className={sharedStyles.statItem}>
                <Icon
                  size="sm"
                  name="downloadSolid"
                  className={sharedStyles.statIcon}
                />
                <span>{article.downloadsTotal}</span>
              </div>
              <div className={sharedStyles.statItem}>
                <Icon
                  size="sm"
                  name="commentSolid"
                  className={sharedStyles.statIcon}
                />
                <span>{article.commentsTotal}</span>
              </div>
              <div className={sharedStyles.statItem}>
                <Icon
                  size="sm"
                  name="thumbsUpSolid"
                  className={sharedStyles.statIcon}
                />
                <span>{article.likesTotal}</span>
              </div>
            </div>
          </div>
        );
      })}

      {objects.length === 0 && (
        <div className={sharedStyles.emptyState}>
          <p>No saved articles found.</p>
        </div>
      )}
    </div>
  );
};

export default SavedArticlesList;
