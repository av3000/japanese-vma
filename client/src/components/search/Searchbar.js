import React, { Component } from 'react';

class Searchbar extends Component {
    constructor(props){
        super(props);
        this.state = {
            keyword: "",
            sortByWhat: "new",
            filterType: 20
        };
        
        this.onSubmit = this.onSubmit.bind(this);
        this.handleChange = this.handleChange.bind(this);
    };

    onSubmit(e){
        e.preventDefault();
        
        let data = {
            keyword: this.state.keyword,
            sortByWhat: this.state.sortByWhat,
            filterType: this.state.filterType
        };

        this.props.fetchQuery(data);
    }
    
    handleChange(e){
        this.setState({ [e.target.name]: e.target.value });
    }
    
    render() {
        return (
            <div className="container">
                <form onSubmit={this.onSubmit}>
                <div className="row">
                    <div className="col-lg-4 col-md-6 col-sm-12 mt-3">
                        <input  onChange={this.handleChange}
                                className="form-control form-control-sm"
                                name="keyword" type="text" placeholder="Ex.: title, text, #tag"
                                value={this.state.keyword}
                                aria-label="Search"
                        />
                    </div>
                    <div className="col-lg-4 col-md-4 col-sm-12 mt-3">
                    {this.props.searchType === "posts" ? (
                        <select name="filterType" value={this.state.filterType} className="form-control form-control-sm" onChange={this.handleChange}>
                            <option value="20">All</option>
                            <option value="1">Content-related</option>
                            <option value="2">Off-topic</option>
                            <option value="3">FAQ</option>
                            <option value="4">Technical</option>
                            <option value="5">Bug</option>
                            <option value="6">Feedback</option>
                            <option value="7">Announcement</option>
                        </select> 
                    ): ""}
                    {this.props.searchType === "articles" ? (
                        <select name="filterType" value={this.state.filterType} className="form-control form-control-sm" onChange={this.handleChange}>
                            <option value="20">All</option>
                            <option value="1">N1</option>
                            <option value="2">N2</option>
                            <option value="3">N3</option>
                            <option value="4">N4</option>
                            <option value="5">N5</option>
                            <option value="6">Uncommon</option>
                        </select> 
                    ): ""}
                    {this.props.searchType === "lists" ? (
                        <select name="filterType" value={this.state.filterType} className="form-control form-control-sm" onChange={this.handleChange}>
                            <option value="20">All</option>
                            <option value="5">Radicals</option>
                            <option value="6">Kanjis</option>
                            <option value="7">Words</option>
                            <option value="8">Sentences</option>
                            <option value="9">Articles</option>
                        </select> 
                    ): ""}
                    </div>
                    <div className="col-lg-2 col-md-2 col-sm-4 mt-3">
                        <select name="sortByWhat" value={this.state.sortByWhat} className="form-control form-control-sm" onChange={this.handleChange}>
                            <option value="new">Newest</option>
                            <option value="pop">Popular</option>
                        </select> 
                    </div>
                    <div className="col-lg-2">
                        <button className="btn btn-outline fas fa-search brand-button mt-3" aria-hidden="true"> search</button>
                    </div>
                </div>
                </form>
            </div>
        )};
}

export default Searchbar;




