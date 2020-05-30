import React from 'react';
// import Moment from 'react-moment';
import { Link } from 'react-router-dom';
import DefaultArticleImg from '../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg';
import BookmarkImg from '../../assets/icons/bookmark-icon.svg';
import './ArticleItem.css';

const ArticleItem = ({ 
    id,
    created_at,
    jp_year,
    jp_month,
    jp_day,
    jp_hour,
    title_jp, 
    commentsTotal, 
    viewsTotal, 
    likesTotal, 
    downloadsTotal,
    hashtags
}) => (
    <div className="col-lg-4 col-md-6 col-sm-8">
        <div className="card mb-4 shadow-sm">
            <Link to={'/article/' + id}>
            <img src={DefaultArticleImg}
                alt="article-image"
                height="225" width="100%"
                className="timelines-image hover"     
            />
            </Link>
            <div className="card-body">
                <Link to={'/article/' + id} className="article-title-link">
                    <h4 className="card-text article-title"> {title_jp}</h4> 
                </Link>
                <p> {hashtags.map(tag => <Link key={tag.id} className="tag-link" to="/">{tag.content} </Link>)} </p>
                <p className="text-muted">
                    {/* <Moment className="text-muted" format="Do MMM YYYY">
                        {created_at}
                    </Moment> */}
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
        </div>
    </div>
);

export default ArticleItem;