import React from "react";
import { Link } from "react-router-dom";
import DefaultArticleImg from "../../assets/images/magic-mary-B5u4r8qGj88-unsplash.jpg";
import "./ArticleItem.css";
import Hashtags from "../ui/hashtags";

const ArticleItem = ({
  id,
  jp_year,
  jp_month,
  jp_day,
  jp_hour,
  title_jp,
  commentsTotal,
  viewsTotal,
  likesTotal,
  hashtags,
  n1,
  n2,
  n3,
  n4,
  n5,
  uncommon,
}) => (
  <div className="col-lg-4 col-md-6 col-sm-8">
    <div className="card mb-4 shadow-sm">
      <Link to={"/article/" + id}>
        <img
          src={DefaultArticleImg}
          alt="article-logo"
          height="225"
          width="100%"
          className="timelines-image hover"
        />
      </Link>
      <div className="card-body">
        <Link to={"/article/" + id} className="article-title-link">
          <h4 className="card-text article-title"> {title_jp}</h4>
        </Link>
        <Hashtags hashtags={hashtags} />{" "}
        <p className="text-muted">
          {jp_year} {jp_month} {jp_day} {jp_hour}
        </p>
        <p className="text-muted">
          {viewsTotal} views &nbsp;
          {commentsTotal} comments&nbsp;
          {likesTotal} likes
        </p>
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
      </div>
    </div>
  </div>
);

export default ArticleItem;
