import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import AcademiaWelcome from "./AcademiaWelcome.vue";

const mocks = vi.hoisted(() => ({
    logout: vi.fn(),
}));

vi.mock("@inertiajs/vue3", () => ({
    Head: { template: "<span />" },
    Link: {
        props: ["href"],
        template: '<a :href="href"><slot /></a>',
    },
    useForm: () => ({ post: mocks.logout, processing: false }),
}));

const statistics = {
    activeUsers: 12,
    exams: {
        total: 34,
        pending: 5,
        published: 27,
        rejected: 2,
    },
    activity: {
        consultations: 86,
        downloads: 41,
    },
};

describe("Academia dashboard", () => {
    it("renders platform statistics when the user has permission", () => {
        const wrapper = mount(AcademiaWelcome, {
            props: {
                canViewSchoolStats: true,
                statistics,
            },
        });

        const section = wrapper.get("#school-statistics");

        expect(section.text()).toContain("Comptes actifs");
        expect(section.text()).toContain("Épreuves en attente");
        expect(section.text()).toContain("Consultations enregistrées");
        expect(section.text()).toContain("Téléchargements enregistrés");
        expect(section.text()).toContain("86");
    });

    it("does not render statistics when the user lacks permission", () => {
        const wrapper = mount(AcademiaWelcome, {
            props: {
                canViewSchoolStats: false,
                statistics,
            },
        });

        expect(wrapper.find("#school-statistics").exists()).toBe(false);
        expect(wrapper.text()).not.toContain("Comptes actifs");
        expect(wrapper.text()).not.toContain("86");
    });
});
