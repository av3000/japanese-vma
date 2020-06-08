import React from 'react';
// import Moment from 'react-moment';
import { Link } from 'react-router-dom';
import DefaultArticleImg from '../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';

const DashboardListItem = ({ 
    
    id,
    title,
    publicity,
    commentsTotal,
    likesTotal,
    viewsTotal,
    downloadsTotal,
    hashtags,
    currentUser,
    listType
}) => (
    <div className="row">
            <div className="col-md-8 pb-3 mb-0 border-bottom border-gray">
                <p>
                    {title}
                </p>
                tags: {hashtags.map(tag => <span key={tag.id} className="tag-link">{tag.content} </span>)}
            </div>
            <div className="col-md-4">
                <Link to={`/list/${id}`}>
                    <strong className="d-block text-gray-dark float-right">
                         <i className="fas fa-external-link-alt"></i>
                    </strong>
                </Link>
                <small className="d-block text-muted">
                        <span>
                         {commentsTotal}&nbsp;Comments 
                        </span>
                        <span>
                         {viewsTotal}&nbsp;Views
                        </span>
                        <span>
                         {likesTotal}&nbsp;Likes &nbsp;
                        </span>
                </small>
                <small className="d-block text-muted">
                    <span>
                       {listType}
                    </span>
                </small>
                
            </div>
        {/* <div className="card mb-4 shadow-sm">
            <div className="card-body">
                <Link to={'/post/' + id} className="article-title-link">
                    <h4 className="card-text article-title"> {title_jp}</h4> 
                </Link>
                <p> {hashtags.map(tag => <span key={tag.id} className="tag-link" to="/">{tag.content} </span>)} </p>
                <p className="text-muted">
                    <Moment className="text-muted" format="Do MMM YYYY">
                        {created_at}
                    </Moment> 
                    {jp_year} {jp_month} {jp_day} {jp_hour}
                </p>
                <p className="text-muted">
                    {viewsTotal+30} views &nbsp;
                    {commentsTotal} comments&nbsp;
                    {likesTotal} likes
                </p>
                <hr/>
                <div className="d-flex justify-content-between align-items-center text-muted">
                        <span>
                         {commentsTotal}&nbsp;N1 
                        </span>
                        <span>
                         {viewsTotal}&nbsp;N2
                        </span>
                        <span>
                         {likesTotal}&nbsp;N3
                        </span>
                        <span>
                         {downloadsTotal}&nbsp;N4
                        </span>
                        <span>
                         {downloadsTotal}&nbsp;N5
                        </span>
                </div>
            </div>
        </div> */}
    </div>
);

export default DashboardListItem;