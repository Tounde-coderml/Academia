import { mount } from "@vue/test-utils";
import { beforeEach, describe, expect, it, vi } from "vitest";
import PersonalEpreuves from "./PersonalEpreuves.vue";

const mocks = vi.hoisted(() => ({
    routerDelete: vi.fn(),
}));

vi.mock("@inertiajs/vue3", () => ({
    Head: { template: "<span />" },
    Link: {
        props: ["href", "ariaCurrent"],
        template: '<a :href="href"><slot /></a>',
    },
    router: { delete: mocks.routerDelete },
}));

const exam = {
    id: 12,
    title: "Examen d’algèbre",
    filiere: "Informatique",
    niveau: "L1",
    matiere: "Algèbre",
    academic_year: "2026-2027",
    semester: "S1",
    type: "controle_continu",
};

const pagination = {
    data: [exam],
    links: [
        { url: null, label: "Previous", active: false },
        { url: "/mes-favoris?page=1", label: "1", active: true },
        { url: null, label: "Next", active: false },
    ],
};

describe("personal favorites and history", () => {
    beforeEach(() => {
        mocks.routerDelete.mockClear();
    });

    it("lists the user's favorites and removes one on request", async () => {
        const wrapper = mount(PersonalEpreuves, {
            props: { mode: "favorites", epreuves: pagination },
        });

        expect(wrapper.text()).toContain("Examen d’algèbre");
        expect(wrapper.find('a[href="/epreuves/12"]').exists()).toBe(true);
        await wrapper.get("button").trigger("click");

        expect(mocks.routerDelete).toHaveBeenCalledWith("/epreuves/12/favori", {
            preserveScroll: true,
        });
    });

    it("shows activity details without favorite controls", () => {
        const wrapper = mount(PersonalEpreuves, {
            props: {
                mode: "history",
                epreuves: {
                    ...pagination,
                    data: [
                        {
                            ...exam,
                            action: "consultation",
                            occurred_at: "2026-11-08 10:00:00",
                        },
                    ],
                },
            },
        });

        expect(wrapper.text()).toContain("Consultée");
        expect(wrapper.text()).toContain("2026-11-08 10:00:00");
        expect(wrapper.find("button").exists()).toBe(false);
    });
});
