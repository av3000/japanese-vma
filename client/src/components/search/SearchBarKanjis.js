import React, { Component } from 'react';

class SearchBarKanjis extends Component {
    constructor(props){
        super(props);
        this.state = {
            keyword: "",
            filterType: 20
        };
        
        this.onSubmit = this.onSubmit.bind(this);
        this.handleChange = this.handleChange.bind(this);
    };

    onSubmit(e){
        e.preventDefault();
        
        let data = {
            keyword: this.state.keyword,
            filterType: this.state.filterType
        };

        this.props.fetchQuery(data);
    }
    
    handleChange(e){
        
        this.setState({ [e.target.name]: e.target.value });
    }
    
    render() {

    return (
            <div className="col-lg-12">
                <form onSubmit={this.onSubmit}>
                <div className="row justify-content-center">
                    <div className="col-lg-4 col-md-6 mb-2">
                        <label>Keyword:</label>
                        <input  onChange={this.handleChange}
                                className="form-control form-control-sm"
                                name="keyword" type="text" placeholder="Search"
                                value={this.state.keyword}
                                aria-label="Search"
                        />
                    </div>
                    <div className="col-lg-4 col-md-4 col-sm-12 mb-2">
                        <label>JLPT:</label>
                        <select name="filterType" value={this.state.filterType} className="form-control form-control-sm" onChange={this.handleChange}>
                            <option value="20">All</option>
                            <option value="1">N1</option>
                            <option value="2">N2</option>
                            <option value="3">N3</option>
                            <option value="4">N4</option>
                            <option value="5">N5</option>
                            <option value="6">Uncommon</option>
                        </select>
                    </div>
                </div>
                <div className="row  justify-content-center">
                    <div className="col-lg-8 col-md-10 mx-auto">
                        <button className="btn btn-outline fas fa-search fa-lg brand-button mt-3" aria-hidden="true"></button>
                    </div>
                </div>
                </form>
            </div>
    )};
}

export default SearchBarKanjis;




