package ru.emoo.web.dto;

import javax.validation.constraints.NotEmpty;

public class BookIdToRemove {

    @NotEmpty
    private Integer id;

    public Integer getId() {
        return id;
    }

    public void setId(Integer id) {
        this.id = id;
    }
}
