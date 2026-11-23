<?php

declare(strict_types=1);

namespace App\Application\Users\Support;

/**
 * The panel permission catalogue as a type. One case per entry in config/permission-catalog.php,
 * which keeps the labels and groups; PanelPermissionCatalogTest fails if the two drift. Checks take
 * a case, never a string, so a typo such as `article.update` cannot compile into a silent deny.
 */
enum PanelPermission: string
{
    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesDelete = 'roles.delete';
    case UsersView = 'users.view';
    case UsersUpdate = 'users.update';
    case RolesAssign = 'roles.assign';
    case ActivityView = 'activity.view';
    case ArticlesView = 'articles.view';
    case ArticlesUpdate = 'articles.update';
    case ArticlesDelete = 'articles.delete';
    case CataloguesView = 'catalogues.view';
    case CataloguesUpdate = 'catalogues.update';
    case CataloguesDelete = 'catalogues.delete';
    case CommentsView = 'comments.view';
    case CommentsDelete = 'comments.delete';
    case PostsView = 'posts.view';
    case PostsModerate = 'posts.moderate';
    case HashtagsManage = 'hashtags.manage';
    case StudyView = 'study.view';
    case SentencesModerate = 'sentences.moderate';
    case OperationsView = 'operations.view';
    case OperationsManage = 'operations.manage';
}
